<?php
namespace App\Console\Commands;

use App\Models\AiDailyPlan;
use App\Models\ModelLog;
use App\Models\Trade;
use App\Models\TradeReview;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AiReviewTrades extends Command
{
   protected $signature = 'ai:review-trades {--model_id=} {--days=7}';
   protected $description = 'Review recently closed trades and classify failure reasons';
   public function handle(): int
   {
       $days = (int) $this->option('days');
       $modelId = $this->option('model_id');
       $query = Trade::query()
           ->whereNotNull('closed_at')
           ->where('closed_at', '>=', now()->subDays($days));
       if ($modelId) {
           $query->where('ai_model_id', $modelId);
       }
       $trades = $query->orderBy('closed_at')->get();
       $this->info("Reviewing {$trades->count()} closed trades...");
       foreach ($trades as $trade) {
           $this->reviewTrade($trade);
       }
       $this->info('Done.');
       return self::SUCCESS;
   }

    protected function reviewTrade(Trade $trade): void
    {
       $symbol = strtoupper(trim($trade->symbol ?? $trade->ticker ?? ''));
       $openedAt = $trade->opened_at ? Carbon::parse($trade->opened_at) : null;
       $closedAt = $trade->closed_at ? Carbon::parse($trade->closed_at) : null;
       $plan = AiDailyPlan::query()
           ->where('ai_model_id', $trade->ai_model_id)
           ->when($openedAt, fn ($q) => $q->whereDate('trade_date', $openedAt->toDateString()))
           ->latest('id')
           ->first();
       $planItems = collect(data_get($plan, 'plan_json', []))
           ->filter(fn ($item) => (bool) ($item['approved'] ?? true))
           ->values();
       $planAligned = $planItems->contains(function ($item) use ($symbol) {
           $sym = strtoupper(trim($item['symbol'] ?? $item['ticker'] ?? ''));
           return $sym === $symbol;
       });
       $planItem = $planItems->first(function ($item) use ($symbol) {
           $sym = strtoupper(trim($item['symbol'] ?? $item['ticker'] ?? ''));
           return $sym === $symbol;
       });
       /*
       |--------------------------------------------------------------------------
       | Entry / Exit logs
       |--------------------------------------------------------------------------
       | Only use real decision-like actions.
       | Exclude noisy actions such as:
       | - SCANNER_CANDIDATES
       | - PREMARKET_PLAN
       | - OPEN_POSITION_PRICE
       | - TICK_TOKEN_DEBUG
       |--------------------------------------------------------------------------
       */
       $entryLogs = ModelLog::query()
           ->where('ai_model_id', $trade->ai_model_id)
           ->whereIn('action', ['OPEN', 'HOLD'])
           ->when($openedAt, fn ($q) => $q->where('created_at', '>=', $openedAt->copy()->subHours(2)))
           ->when($openedAt, fn ($q) => $q->where('created_at', '<=', $openedAt->copy()->addHours(2)))
           ->orderBy('created_at')
           ->get();
       $exitLogs = ModelLog::query()
           ->where('ai_model_id', $trade->ai_model_id)
           ->whereIn('action', ['CLOSE', 'HOLD'])
           ->when($closedAt, fn ($q) => $q->where('created_at', '>=', $closedAt->copy()->subHours(2)))
           ->when($closedAt, fn ($q) => $q->where('created_at', '<=', $closedAt->copy()->addHours(2)))
           ->orderBy('created_at')
           ->get();
       // Best case: exact order-symbol match in an OPEN log
       $entryLog = $entryLogs->first(function ($log) use ($symbol) {
           return $this->modelLogMatchesSymbol($log, $symbol);
       });
       // Fallback: nearest OPEN log by time
       if (!$entryLog && $openedAt) {
           $entryLog = $entryLogs
               ->where('action', 'OPEN')
               ->sortBy(fn ($log) => abs($log->created_at->diffInSeconds($openedAt, false)))
               ->first();
       }
       // Last fallback: nearest HOLD log by time
       if (!$entryLog && $openedAt) {
           $entryLog = $entryLogs
               ->where('action', 'HOLD')
               ->sortBy(fn ($log) => abs($log->created_at->diffInSeconds($openedAt, false)))
               ->first();
       }
       // Best case: exact order-symbol match in a CLOSE log
       $exitLog = $exitLogs->first(function ($log) use ($symbol) {
           return $this->modelLogMatchesSymbol($log, $symbol);
       });
       // Fallback: nearest CLOSE log by time
       if (!$exitLog && $closedAt) {
           $exitLog = $exitLogs
               ->where('action', 'CLOSE')
               ->sortBy(fn ($log) => abs($log->created_at->diffInSeconds($closedAt, false)))
               ->first();
       }
       // Last fallback: nearest HOLD log by time
       if (!$exitLog && $closedAt) {
           $exitLog = $exitLogs
               ->where('action', 'HOLD')
               ->sortBy(fn ($log) => abs($log->created_at->diffInSeconds($closedAt, false)))
               ->first();
       }
       $reviewIncomplete = !$entryLog;
       $strategy = $trade->strategy
           ?? $this->extractStrategyFromLog($entryLog)
           ?? data_get($planItem, 'strategy');
       $regimeAtEntry = $this->extractRegimeFromLog($entryLog);
       $regimeAtExit = $this->extractRegimeFromLog($exitLog);
       $distanceToEntryPct = $this->calculateDistanceToEntryPct($trade, $planItem);
       $rewardRisk = $this->calculateRewardRisk($trade, $planItem);
       $relativeVolume = (float) (
           $this->extractWatchValue($entryLog, $symbol, 'relative_volume') ?? 0
       );
       $regimeMismatch = $this->isRegimeMismatch($strategy, $regimeAtEntry);
       $entryScore = 10;
       if (! $planAligned) $entryScore -= 4;
       if ($distanceToEntryPct > 1.5) $entryScore -= 2;
       if ($relativeVolume > 0 && $relativeVolume < 1.0) $entryScore -= 1;
       if ($regimeMismatch) $entryScore -= 2;
       if ($rewardRisk > 0 && $rewardRisk < 1.5) $entryScore -= 2;
       if ($reviewIncomplete) $entryScore -= 2;
       $entryScore = max(0, min(10, $entryScore));
       $invalidatedButHeld = false;
       $lateExit = false;
       $gaveBackLargeProfit = false;
       $exitScore = 10;
       if ($invalidatedButHeld) $exitScore -= 4;
       if ($lateExit) $exitScore -= 3;
       if ($gaveBackLargeProfit) $exitScore -= 2;
       $exitScore = max(0, min(10, $exitScore));
       $tradeShouldHaveBeenHold = $entryScore < 8;
       $netPnl = (float) ($trade->net_pnl ?? $trade->pnl ?? 0);
       $rMultiple = $this->calculateRMultiple($trade);
       $failureReason = null;
       $improvementAction = 'no_change';
       if ($reviewIncomplete) {
           $failureReason = 'missing_entry_context';
           $improvementAction = 'improve_logging_linkage';
       } elseif ($netPnl >= 0) {
           $failureReason = null;
           $improvementAction = 'no_change';
       } elseif (! $planAligned) {
           $failureReason = 'not_plan_aligned';
           $improvementAction = 'prefer_hold';
       } elseif ($entryScore <= 4) {
           $failureReason = 'bad_entry';
           $improvementAction = 'raise_quality_threshold';
       } elseif ($exitScore <= 4 && $invalidatedButHeld) {
           $failureReason = 'late_exit';
           $improvementAction = 'exit_faster';
       } elseif ($regimeMismatch) {
           $failureReason = 'wrong_regime';
           $improvementAction = 'disable_strategy_in_regime';
       } elseif ($tradeShouldHaveBeenHold) {
           $failureReason = 'should_hold';
           $improvementAction = 'prefer_hold';
       } else {
           $failureReason = 'normal_loss';
           $improvementAction = 'no_change';
       }
       // dd([
       //         'ai_model_id' => $trade->ai_model_id,
       //         'symbol' => $symbol,
       //         'strategy' => $strategy,
       //         'regime_at_entry' => $regimeAtEntry,
       //         'regime_at_exit' => $regimeAtExit,
       //         'plan_aligned' => $planAligned,
       //         'should_have_opened' => $entryScore >= 8,
       //         'should_have_closed_earlier' => $invalidatedButHeld,
       //         'entry_quality_score' => $entryScore,
       //         'exit_quality_score' => $exitScore,
       //         'failure_reason' => $failureReason,
       //         'improvement_action' => $improvementAction,
       //         'r_multiple' => $rMultiple,
       //         'net_pnl' => $netPnl,
       //         'review_payload' => [
       //             'entry_log_found' => (bool) $entryLog,
       //             'exit_log_found' => (bool) $exitLog,
       //             'review_incomplete' => $reviewIncomplete,
       //             'entry_log_id' => $entryLog?->id,
       //             'exit_log_id' => $exitLog?->id,
       //             'distance_to_entry_pct' => $distanceToEntryPct,
       //             'relative_volume' => $relativeVolume,
       //             'reward_risk' => $rewardRisk,
       //             'regime_mismatch' => $regimeMismatch,
       //             'plan_item' => $planItem,
       //         ],
       //         'review_source' => 'rule_engine',
       //     ]);
       TradeReview::updateOrCreate(
           ['trade_id' => $trade->id],
           [
               'ai_model_id' => $trade->ai_model_id,
               'symbol' => $symbol,
               'strategy' => $strategy,
               'regime_at_entry' => $regimeAtEntry,
               'regime_at_exit' => $regimeAtExit,
               'plan_aligned' => $planAligned,
               'should_have_opened' => $entryScore >= 8,
               'should_have_closed_earlier' => $invalidatedButHeld,
               'entry_quality_score' => $entryScore,
               'exit_quality_score' => $exitScore,
               'failure_reason' => $failureReason,
               'improvement_action' => $improvementAction,
               'r_multiple' => $rMultiple,
               'net_pnl' => $netPnl,
               'review_payload' => [
                   'entry_log_found' => (bool) $entryLog,
                   'exit_log_found' => (bool) $exitLog,
                   'review_incomplete' => $reviewIncomplete,
                   'entry_log_id' => $entryLog?->id,
                   'exit_log_id' => $exitLog?->id,
                   'distance_to_entry_pct' => $distanceToEntryPct,
                   'relative_volume' => $relativeVolume,
                   'reward_risk' => $rewardRisk,
                   'regime_mismatch' => $regimeMismatch,
                   'plan_item' => $planItem,
               ],
               'review_source' => 'rule_engine',
           ]
       );
    }

    protected function modelLogMatchesSymbol(ModelLog $log, string $symbol): bool
    {
       $payload = is_array($log->payload) ? $log->payload : [];
       $possibleOrderPaths = [
           data_get($payload, 'decision.orders', []),
           data_get($payload, 'orders', []),
           data_get($payload, 'response.orders', []),
       ];
       foreach ($possibleOrderPaths as $orders) {
           if (!is_array($orders)) {
               continue;
           }
           foreach ($orders as $order) {
               $orderSymbol = strtoupper(trim($order['symbol'] ?? $order['ticker'] ?? ''));
               if ($orderSymbol === strtoupper(trim($symbol))) {
                   return true;
               }
           }
       }
       return false;
    }
    protected function extractStrategyFromLog(?ModelLog $log): ?string
    {
        if (!$log) {
            return null;
        }

        $payload = is_array($log->payload) ? $log->payload : [];

        $strategy = data_get($payload, 'decision.strategy', data_get($payload, 'strategy'));

        if (is_array($strategy)) {
            return $strategy['name'] ?? null;
        }

        return is_string($strategy) ? $strategy : null;
    }
    protected function extractRegimeFromLog(?ModelLog $log): ?string
    {
       if (!$log) {
           return null;
       }
       $payload = is_array($log->payload) ? $log->payload : [];
       return data_get($payload, 'state_snapshot.market_context.trend')
           ?? data_get($payload, 'state.market_context.trend')
           ?? data_get($payload, 'state_snapshot.watchlist.0.regime_hint')
           ?? data_get($payload, 'state.watchlist.0.regime_hint');
    }
    protected function extractWatchValue(?ModelLog $log, string $symbol, string $key): mixed
    {
       if (!$log) {
           return null;
       }
       $payload = is_array($log->payload) ? $log->payload : [];
       $watchlists = [
           data_get($payload, 'state_snapshot.watchlist', []),
           data_get($payload, 'state.watchlist', []),
           data_get($payload, 'watchlist', []),
       ];
       foreach ($watchlists as $watchlist) {
           if (!is_array($watchlist)) {
               continue;
           }
           foreach ($watchlist as $item) {
               $ticker = strtoupper(trim($item['ticker'] ?? $item['symbol'] ?? ''));
               if ($ticker === strtoupper(trim($symbol))) {
                   return $item[$key] ?? null;
               }
           }
       }
       return null;
    }

   // protected function extractStrategy(Trade $trade, ?ModelLog $entryLog, ?array $planItem): ?string
   // {
   //     return $trade->strategy
   //         ?? data_get($entryLog?->payload, 'decision.strategy')
   //         ?? data_get($planItem, 'strategy');
   // }
   // protected function extractRegime(?ModelLog $entryLog): ?string
   // {
   //     $payload = $entryLog?->payload ?? [];
   //     if (!is_array($payload)) {
   //         return null;
   //     }
   //     return data_get($payload, 'state_snapshot.market_context.trend')
   //         ?? data_get($payload, 'state_snapshot.watchlist.0.regime_hint');
   // }
   // protected function extractExitRegime($logs): ?string
   // {
   //     $last = $logs->last();
   //     $payload = $last?->payload ?? [];
   //     if (!is_array($payload)) {
   //         return null;
   //     }
   //     return data_get($payload, 'state_snapshot.market_context.trend')
   //         ?? data_get($payload, 'state_snapshot.watchlist.0.regime_hint');
   // }
   protected function calculateDistanceToEntryPct(Trade $trade, ?array $planItem): float
   {
       $entryPrice = (float) ($trade->entry_price ?? 0);
       if ($entryPrice <= 0 || !$planItem) {
           return 0.0;
       }
       $entryLow = (float) ($planItem['entry_zone_low'] ?? 0);
       $entryHigh = (float) ($planItem['entry_zone_high'] ?? 0);
       if ($entryLow <= 0 && $entryHigh <= 0) {
           return 0.0;
       }
       $ref = $entryLow > 0 ? $entryLow : $entryHigh;
       if ($ref <= 0) {
           return 0.0;
       }
       return round(abs(($entryPrice - $ref) / $ref) * 100, 4);
   }
   protected function calculateRewardRisk(Trade $trade, ?array $planItem): float
   {
       $entry = (float) ($trade->entry_price ?? 0);
       $stop = (float) ($trade->stop_price ?? ($planItem['invalidation'] ?? 0));
       $target = (float) ($trade->target_price ?? ($planItem['target_1'] ?? 0));
       if ($entry <= 0 || $stop <= 0 || $target <= 0) {
           return 0.0;
       }
       $risk = abs($entry - $stop);
       $reward = abs($target - $entry);
       if ($risk <= 0) {
           return 0.0;
       }
       return round($reward / $risk, 4);
   }
   protected function calculateRMultiple(Trade $trade): ?float
   {
       $entry = (float) ($trade->entry_price ?? 0);
       $stop = (float) ($trade->stop_price ?? 0);
       $qty = (float) ($trade->qty ?? 0);
       $netPnl = (float) ($trade->net_pnl ?? $trade->pnl ?? 0);
       $riskPerShare = abs($entry - $stop);
       if ($riskPerShare <= 0 || $qty <= 0) {
           return null;
       }
       $initialRisk = $riskPerShare * $qty;
       if ($initialRisk <= 0) {
           return null;
       }
       return round($netPnl / $initialRisk, 4);
   }
   protected function isRegimeMismatch(?string $strategy, ?string $regime): bool
   {
       $strategy = strtolower((string) $strategy);
       $regime = strtolower((string) $regime);
       if ($strategy === '' || $regime === '') {
           return false;
       }
       if (str_contains($strategy, 'breakout') && in_array($regime, ['mean_reversion', 'no_trade'], true)) {
           return true;
       }
       if (str_contains($strategy, 'momentum') && in_array($regime, ['mean_reversion', 'no_trade'], true)) {
           return true;
       }
       if (str_contains($strategy, 'vwap') && in_array($regime, ['breakout', 'momentum', 'trend_follow'], true)) {
           return true;
       }
       return false;
   }
}
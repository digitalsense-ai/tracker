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
       $symbol = strtoupper($trade->symbol ?? $trade->ticker ?? '');
       $openedAt = $trade->opened_at ? Carbon::parse($trade->opened_at) : null;
       $closedAt = $trade->closed_at ? Carbon::parse($trade->closed_at) : null;
       $plan = AiDailyPlan::query()
           ->where('ai_model_id', $trade->ai_model_id)
           ->whereDate('trade_date', optional($openedAt)->toDateString())
           ->latest('id')
           ->first();
       $planItems = collect(data_get($plan, 'plan_json', []))
           ->filter(fn ($item) => (bool) ($item['approved'] ?? true))
           ->values();
       $planAligned = $planItems->contains(function ($item) use ($symbol) {
           $sym = strtoupper($item['symbol'] ?? $item['ticker'] ?? '');
           return $sym === $symbol;
       });
       $planItem = $planItems->first(function ($item) use ($symbol) {
           $sym = strtoupper($item['symbol'] ?? $item['ticker'] ?? '');
           return $sym === $symbol;
       });
       $logs = ModelLog::query()
           ->where('ai_model_id', $trade->ai_model_id)
           ->when($openedAt, fn ($q) => $q->where('created_at', '>=', $openedAt->copy()->subMinutes(30)))
           ->when($closedAt, fn ($q) => $q->where('created_at', '<=', $closedAt->copy()->addMinutes(30)))
           ->orderBy('created_at')
           ->get();
       $entryLog = $logs->first(function ($log) use ($symbol) {
           $payload = is_array($log->payload) ? $log->payload : [];
           $decision = $payload['decision'] ?? [];
           $orders = $decision['orders'] ?? [];
           foreach ($orders as $order) {
               if (strtoupper($order['symbol'] ?? '') === $symbol) {
                   return true;
               }
           }
           return false;
       });
       $strategy = $this->extractStrategy($trade, $entryLog, $planItem);
       $regimeAtEntry = $this->extractRegime($entryLog);
       $regimeAtExit = $this->extractExitRegime($logs);
       $distanceToEntryPct = $this->calculateDistanceToEntryPct($trade, $planItem);
       $rewardRisk = $this->calculateRewardRisk($trade, $planItem);
       $relativeVolume = (float) data_get($entryLog?->payload, 'state_snapshot.watchlist.0.relative_volume', 0);
       $regimeMismatch = $this->isRegimeMismatch($strategy, $regimeAtEntry);
       $entryScore = 10;
       if (! $planAligned) $entryScore -= 4;
       if ($distanceToEntryPct > 1.5) $entryScore -= 2;
       if ($relativeVolume > 0 && $relativeVolume < 1.0) $entryScore -= 1;
       if ($regimeMismatch) $entryScore -= 2;
       if ($rewardRisk > 0 && $rewardRisk < 1.5) $entryScore -= 2;
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
       $failureReason = null;
       $improvementAction = 'no_change';
       $netPnl = (float) ($trade->net_pnl ?? $trade->pnl ?? 0);
       if ($netPnl >= 0) {
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
       $rMultiple = $this->calculateRMultiple($trade);
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
   protected function extractStrategy(Trade $trade, ?ModelLog $entryLog, ?array $planItem): ?string
   {
       return $trade->strategy
           ?? data_get($entryLog?->payload, 'decision.strategy')
           ?? data_get($planItem, 'strategy');
   }
   protected function extractRegime(?ModelLog $entryLog): ?string
   {
       $payload = $entryLog?->payload ?? [];
       if (!is_array($payload)) {
           return null;
       }
       return data_get($payload, 'state_snapshot.market_context.trend')
           ?? data_get($payload, 'state_snapshot.watchlist.0.regime_hint');
   }
   protected function extractExitRegime($logs): ?string
   {
       $last = $logs->last();
       $payload = $last?->payload ?? [];
       if (!is_array($payload)) {
           return null;
       }
       return data_get($payload, 'state_snapshot.market_context.trend')
           ?? data_get($payload, 'state_snapshot.watchlist.0.regime_hint');
   }
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
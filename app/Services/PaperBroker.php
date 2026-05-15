<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\Position;
use App\Models\Trade;
use App\Models\StrategyProfile;
use App\Models\AiDailyPlan;
use App\Models\ModelLog;

use Carbon\Carbon;
use App\Services\PriceFeed\PriceFeedInterface;

class PaperBroker
{
    public function __construct(
        private PriceFeedInterface $feed,
        private PortfolioService $portfolio,
        protected MarketData $marketData
    ) {}

    public function execute(AiModel $model, array $orders): void
    {
        foreach ($orders as $o) {
            $type = strtolower($o['type'] ?? $o['action'] ?? 'open');
            $ticker = strtoupper($o['ticker'] ?? '');
            if (!$ticker) continue;

            if ($type === 'open') $this->open($model, $ticker, $o);
            elseif ($type === 'close') $this->close($model, $ticker, $o);
            elseif ($type === 'adjust') $this->adjust($model, $ticker, $o);
        }
    }

    public function open(AiModel $model, string $ticker, array $o): array
    {
        $qty   = (float)($o['qty'] ?? 0);
        $side  = strtolower($o['side'] ?? 'long');
        $price = (float)($o['entry'] ?? 0);
        if ($price <= 0) $price = $this->feed->last($ticker) ?? 100.0;

        $stopPrice = isset($o['stop']) ? (float) $o['stop'] : null;
        $targetPrice = isset($o['target']) ? (float) $o['target'] : null;
        $policy = TakeProfitPolicy::resolve(null, null, $o);

        $trade = Trade::create([
            'ai_model_id' => $model->id,
            'ticker' => $ticker,
            'side' => $side,
            'entry_price' => $price,
            'qty' => $qty,
            'opened_at' => Carbon::now(),
            'notional_entry' => $qty * $price,
        ]);

        $pos = Position::create([
            'ai_model_id' => $model->id,
            'ticker' => $ticker,
            'side' => $side,
            'qty' => $qty,
            'remaining_qty' => $qty,
            'avg_price' => $price,
            'stop_price' => $stopPrice,
            'initial_stop_price' => $stopPrice,
            'target_price' => $targetPrice,
            'tp1_hit' => false,
            'runner_active' => false,
            'highest_price' => $price,
            'tp_model' => $policy['tp_model'],
            'tp1_close_pct' => $policy['tp1_close_pct'],
            'move_sl_to_break_even_on_tp1' => $policy['move_sl_to_break_even_on_tp1'],
            'runner_trailing_enabled' => $policy['runner_trailing_enabled'],
            'runner_trail_distance_rr' => $policy['runner_trail_distance_rr'],
            'leverage' => $o['leverage'] ?? 1,
            'margin' => $o['margin'] ?? 0,
            'unrealized_pnl' => 0,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $this->portfolio->applyOpen($model, $qty * $price);
        return ['ok'=>true,'type'=>'open','trade_id'=>$trade->id,'position_id'=>$pos->id];
    }

    public function close(AiModel $model, string $ticker, array $o): array
    {
        $pos = Position::where('ai_model_id', $model->id)->where('ticker', $ticker)->where('status', 'open')->first();
        if (!$pos) return ['ok'=>false,'error'=>'no position'];

        $price = (float)($o['exit'] ?? $o['price'] ?? null);
        if ($price <= 0) $price = $this->feed->last($ticker) ?? 100.0;

        $qtyToClose = $this->positionRemainingQty($pos);
        $pnl = $this->closePositionRemainder($pos, $price, 'MANUAL_CLOSE', $o['reasoning'] ?? 'Manual close');

        $this->portfolio->applyClose($model, $qtyToClose * $price, $pnl);
        return ['ok'=>true,'type'=>'close','pnl'=>$pnl];
    }

    public function adjust(AiModel $model, string $ticker, array $o): array
    {
        $pos = Position::where('ai_model_id',$model->id)->where('ticker',$ticker)->where('status','open')->first();
        if (!$pos) return ['ok'=>false,'error'=>'no position'];
        $pos->update([
            'stop_price' => $o['stop'] ?? $pos->stop_price,
            'target_price' => $o['target'] ?? $pos->target_price,
        ]);
        return ['ok'=>true,'type'=>'adjust'];
    }

    /**
    * Process a decision from the AI for a single model.
    * $decision is what AiDecisionParser::parse() returns.
    */
   public function processDecision(AiModel $model, array $decision): void
   {
       // Normalise top-level action
       $action = strtoupper($decision['action'] ?? 'HOLD');
       $orders = $decision['orders'] ?? [];

       $allowed = $this->getAllowedSymbols($model);

       if (!in_array($action, ['OPEN', 'CLOSE'])) {
           // HOLD (or anything else): no structural changes, just keep equity as is
           $this->recalculateEquity($model);
           return;
       }
       foreach ($orders as $order) {
           // Support either `symbol` or `ticker` from the AI
           $symbol = strtoupper($order['symbol'] ?? $order['ticker'] ?? '');

           if (!$symbol || !in_array($symbol, $allowed, true)) {
              ModelLog::create([
                  'ai_model_id' => $model->id,
                  'action'      => 'ORDER_REJECTED',
                  'payload'     => [
                      'reason'  => 'symbol_not_allowed',
                      'symbol'  => $symbol,
                      'order'   => $order,
                      'allowed' => $allowed,
                  ],
              ]);
              continue; // HARD STOP — skip execution
          }
    
           // Normalise side into our DB enum: 'long' / 'short'
           $rawSide = strtolower($order['side'] ?? $order['direction'] ?? 'long');
           if ($rawSide === 'buy') {
               $side = 'long';
           } elseif ($rawSide === 'sell') {
               $side = 'short';
           } elseif (in_array($rawSide, ['long','short'], true)) {
               $side = $rawSide;
           } else {
               $side = 'long';
           }

           $qty    = (float) ($order['qty'] ?? 0);
           if (!$symbol || $qty <= 0) {
               continue;
           }
           if ($action === 'OPEN') {
               $this->openPosition($model, $symbol, $side, $qty);
           } elseif ($action === 'CLOSE') {
               $this->closePositionBySymbol($model, $symbol, $decision);
           }
       }
       $this->recalculateEquity($model);
   }

   public function openPosition(
       AiModel $model,
       string $symbol,
       string $side,
       float $qty,
       ?float $stopPrice = null,
       ?float $targetPrice = null
    ): void {   
       $marketData = app(\App\Services\MarketData::class);
       $price      = (float) $marketData->getPrice($symbol);

       ModelLog::create([
        'ai_model_id' => $model->id,
        'action' => 'OPEN_POSITION_PRICE',
        'payload' => ['symbol' => $symbol, 'price' => $price],
      ]);

       if ($price <= 0) {
           return;
       }
       // Try to find existing open position first
       $position = Position::where('ai_model_id', $model->id)
           ->where('ticker', $symbol)
           ->where('status', 'open')
           ->first();
       if ($position) {          

           // ADD to existing position
           $oldQty   = (float)$position->qty;
           $newQty   = $oldQty + $qty;
           if ($newQty <= 0) {
               return;
           }
           $oldNotional = $oldQty * (float)$position->avg_price;
           $addNotional = $qty * $price;
           $avgPrice    = ($oldNotional + $addNotional) / $newQty;
           $position->qty        = $newQty;
           $position->remaining_qty = $this->positionRemainingQty($position) + $qty;
           $position->avg_price  = $avgPrice;
           if ($stopPrice !== null) {
               $position->stop_price = $stopPrice;
               $position->initial_stop_price = $position->initial_stop_price ?? $stopPrice;
           }
           if ($targetPrice !== null) {
               $position->target_price = $targetPrice;
           }
           $position->highest_price = $this->bestPrice($position, $price);
           $position->save();
       } else {
          $planItem = [];
          $planJson = AiDailyPlan::where('ai_model_id', $model->id)
              ->orderByDesc('trade_date')
              ->value('plan_json');

          if ($planJson) {
              $plans = $planJson;
              $plan = collect($plans)->firstWhere('symbol', $symbol);

              if ($plan) {
                  $planItem = $plan;
                  $stopPrice   = isset($plan['stop_loss']) ? (float) $plan['stop_loss'] : $stopPrice;
                  $targetPrice = isset($plan['take_profit']) ? (float) $plan['take_profit'] : $targetPrice;
              }
          }

           $profile = StrategyProfile::query()->orderBy('id')->first();
           $policy = TakeProfitPolicy::resolve(null, $profile, $planItem);

           // Create brand new position
           $position               = new Position();
           $position->ai_model_id  = $model->id;
           $position->ticker       = $symbol;
           $position->side         = $side;
           $position->qty          = $qty;
           $position->remaining_qty = $qty;
           $position->avg_price    = $price;
           $position->stop_price   = $stopPrice;
           $position->initial_stop_price = $stopPrice;
           $position->target_price = $targetPrice;
           $position->tp1_hit      = false;
           $position->runner_active = false;
           $position->highest_price = $price;
           TakeProfitPolicy::persistOnPosition($position, $policy);
           $position->status       = 'open';
           $position->opened_at    = now();
           $position->save();
       }
       // ALWAYS create a trade row for every OPEN/ADD
       $trade                 = new Trade();
       $trade->ai_model_id    = $model->id;
       $trade->ticker         = $symbol;
       $trade->side           = $side;
       $trade->qty            = $qty;
       $trade->entry_price    = $price;
       $trade->opened_at      = now();
       $trade->notional_entry = $price * $qty;
       $trade->fees           = 0;
       $trade->net_pnl        = null;

       $trade->date = Carbon::now()->toDateString(); // e.g. "2025-12-09"

      // attach a default strategy profile so NOT NULL constraint passes
      $defaultProfileId = StrategyProfile::query()->orderBy('id')->value('id');
      if ($defaultProfileId) {
        $trade->strategy_profile_id = $defaultProfileId;
      }

      $trade->exit_price     = null;          // placeholder for open trades
      $trade->notional_exit  = null;          // no exit yet      
      $trade->stop_loss     = $stopPrice ?? 0;

       $trade->save();
    }

   public function closePositionBySymbol(AiModel $model, string $symbol, array $decision = []): void
   {
       $position = Position::where('ai_model_id', $model->id)
           ->where('ticker', $symbol)
           ->where('status', 'open')
           ->first();
       if (!$position) {
           return;
       }
       $marketData = app(\App\Services\MarketData::class);       

      // Map of standardized exit reason codes
      $exitReasonMap = [
          'stop_loss'    => ['stop loss', 'fell below stop'],
          'take_profit'  => ['take profit', 'reached target'],
          'manual_close' => ['manual', 'user closed'],
          'market_cond'  => ['market condition', 'volatility'],
      ];

      // Determine code from reasoning
      $reasonText = $decision['reasoning'] ?? '';
      $reasonCode = null;

      foreach ($exitReasonMap as $code => $keywords) {
          foreach ($keywords as $keyword) {
              if (stripos($reasonText, $keyword) !== false) {
                  $reasonCode = strtoupper($code); // e.g., STOP_LOSS
                  break 2;
              }
          }
      }

      $exitPrice = (float) $marketData->getPrice($symbol);
      if ($exitPrice <= 0) {
          ModelLog::create([
              'ai_model_id' => $model->id,
              'action' => 'CLOSE_BLOCKED',
              'payload' => ['symbol'=>$symbol, 'reason'=>'no_exit_price'],
          ]);
          return;
      }

      $this->closePositionRemainder($position, $exitPrice, $reasonCode ?? 'MANUAL_CLOSE', $reasonText);
   }

   /**
    * Apply deterministic TP/runner rules for an open position at the latest price.
    * AI can choose configuration, but this method owns execution.
    */
   public function applyTakeProfitLogic(Position $position, float $price): void
   {
       if ($price <= 0 || $position->status !== 'open') {
           return;
       }

       $this->initializeRunnerState($position, $price);
       $policy = TakeProfitPolicy::resolve($position);

       if (!$policy['take_profit_enabled'] || $policy['tp_model'] === 'no_tp' || !$position->target_price) {
           $this->applyRunnerTrailing($position, $price, $policy);
           return;
       }

       if ($policy['tp_model'] === 'full_exit' && $this->targetReached($position, $price)) {
           $this->closePositionRemainder($position, (float) $position->target_price, 'TAKE_PROFIT', 'Full take-profit target reached');
           return;
       }

       if ($policy['tp_model'] !== 'simple_runner') {
           return;
       }

       if (!$position->tp1_hit && $this->targetReached($position, $price)) {
           $this->handleTp1($position, (float) $position->target_price, $policy);
           return;
       }

       $this->applyRunnerTrailing($position, $price, $policy);
   }

   protected function handleTp1(Position $position, float $price, array $policy): void
   {
       $remainingQty = $this->positionRemainingQty($position);
       if ($remainingQty <= 0) {
           return;
       }

       $closePct = max(0.0, min(1.0, (float) ($policy['tp1_close_pct'] ?? 0.5)));
       $closeQty = round($remainingQty * $closePct, 6);
       if ($closeQty <= 0) {
           return;
       }

       $this->closePartial($position, $closeQty, $price, 'TP1_PARTIAL', 'TP1 partial profit taken');

       $position->refresh();
       $position->tp1_hit = true;
       $position->runner_active = (bool) ($policy['runner_trailing_enabled'] ?? true);
       $position->highest_price = $this->bestPrice($position, $price);

       if ((bool) ($policy['move_sl_to_break_even_on_tp1'] ?? true)) {
           if ($position->side === 'short') {
               $position->stop_price = min((float) $position->stop_price ?: (float) $position->avg_price, (float) $position->avg_price);
           } else {
               $position->stop_price = max((float) $position->stop_price, (float) $position->avg_price);
           }
       }

       $position->save();

       ModelLog::create([
           'ai_model_id' => $position->ai_model_id,
           'action' => 'TP1_SIMPLE_RUNNER_TRIGGERED',
           'payload' => [
               'symbol' => $position->ticker,
               'price' => $price,
               'closed_qty' => $closeQty,
               'remaining_qty' => $this->positionRemainingQty($position),
               'stop_price' => $position->stop_price,
               'runner_active' => $position->runner_active,
               'policy' => $policy,
           ],
       ]);
   }

   protected function applyRunnerTrailing(Position $position, float $price, ?array $policy = null): void
   {
       $policy = $policy ?? TakeProfitPolicy::resolve($position);

       if (!$position->runner_active || !(bool) ($policy['runner_trailing_enabled'] ?? true)) {
           return;
       }

       $initialStop = (float) ($position->initial_stop_price ?? $position->stop_price ?? 0);
       $entry = (float) $position->avg_price;
       $risk = abs($entry - $initialStop);

       if ($risk <= 0) {
           return;
       }

       $position->highest_price = $this->bestPrice($position, $price);
       $trailDistance = $risk * (float) ($policy['runner_trail_distance_rr'] ?? 1.0);

       if ($position->side === 'short') {
           $newStop = (float) $position->highest_price + $trailDistance;
           if (!$position->stop_price || $newStop < (float) $position->stop_price) {
               $position->stop_price = $newStop;
               $position->save();
           }
           return;
       }

       $newStop = (float) $position->highest_price - $trailDistance;
       if ($newStop > (float) $position->stop_price) {
           $position->stop_price = $newStop;
           $position->save();
       }
   }

   protected function closePartial(Position $position, float $qty, float $price, string $reasonCode, string $reasonText): float
   {
       $qty = min($qty, $this->positionRemainingQty($position));
       if ($qty <= 0) {
           return 0.0;
       }

       $sourceTrade = Trade::where('ai_model_id', $position->ai_model_id)
           ->where('ticker', $position->ticker)
           ->whereNull('closed_at')
           ->orderBy('opened_at')
           ->first();

       if (!$sourceTrade) {
           return 0.0;
       }

       $netPnl = $this->calculateNetPnl($position->side, $qty, (float) $sourceTrade->entry_price, $price);

       $partialTrade = new Trade();
       $partialTrade->ai_model_id = $position->ai_model_id;
       $partialTrade->ticker = $position->ticker;
       $partialTrade->side = $position->side;
       $partialTrade->qty = $qty;
       $partialTrade->entry_price = $sourceTrade->entry_price;
       $partialTrade->exit_price = $price;
       $partialTrade->stop_loss = $sourceTrade->stop_loss;             
       $partialTrade->notional_entry = $qty * (float) $sourceTrade->entry_price;
       $partialTrade->notional_exit = $qty * $price;
       $partialTrade->fees = 0;
       $partialTrade->net_pnl = $netPnl;
       $partialTrade->opened_at = $sourceTrade->opened_at;
       $partialTrade->closed_at = now();
       $partialTrade->date = Carbon::now()->toDateString();
       $partialTrade->exit_reason_code = $reasonCode;
       $partialTrade->exit_reason_text = $reasonText;
       if ($sourceTrade->strategy_profile_id) {
           $partialTrade->strategy_profile_id = $sourceTrade->strategy_profile_id;
       }
       $partialTrade->save();

       $sourceTrade->qty = max(0, (float) $sourceTrade->qty - $qty);
       $sourceTrade->notional_entry = (float) $sourceTrade->qty * (float) $sourceTrade->entry_price;
       if ((float) $sourceTrade->qty <= 0) {
           $sourceTrade->closed_at = now();
       }
       $sourceTrade->save();

       $position->remaining_qty = max(0, $this->positionRemainingQty($position) - $qty);
       if ((float) $position->remaining_qty <= 0) {
           $position->status = 'closed';
           $position->closed_at = now();
           $position->unrealized_pnl = 0;
       }
       $position->save();

       return $netPnl;
   }

   protected function closePositionRemainder(Position $position, float $price, string $reasonCode, string $reasonText): float
   {
       $remainingQty = $this->positionRemainingQty($position);
       if ($remainingQty <= 0) {
           return 0.0;
       }

       $totalPnl = 0.0;
       $openTrades = Trade::where('ai_model_id', $position->ai_model_id)
           ->where('ticker', $position->ticker)
           ->whereNull('closed_at')
           ->get();

       foreach ($openTrades as $trade) {
           $qty = (float) $trade->qty;
           if ($qty <= 0) {
               continue;
           }

           $netPnl = $this->calculateNetPnl($trade->side, $qty, (float) $trade->entry_price, $price);
           $trade->exit_price = $price;
           $trade->notional_exit  = $qty * $price;
           $trade->net_pnl    = $netPnl;
           $trade->exit_reason_code = $reasonCode;
           $trade->exit_reason_text = $reasonText;
           $trade->closed_at  = now();
           $trade->save();
           $totalPnl += $netPnl;
       }

       $position->remaining_qty = 0;
       $position->status = 'closed';
       $position->closed_at = now();
       $position->unrealized_pnl = 0;
       $position->save();

       return $totalPnl;
   }

   protected function initializeRunnerState(Position $position, float $price): void
   {
       $changed = false;

       if ($position->remaining_qty === null) {
           $position->remaining_qty = (float) $position->qty;
           $changed = true;
       }

       if ($position->initial_stop_price === null && $position->stop_price !== null) {
           $position->initial_stop_price = (float) $position->stop_price;
           $changed = true;
       }

       if ($position->highest_price === null) {
           $position->highest_price = $price;
           $changed = true;
       }

       $policy = TakeProfitPolicy::resolve($position);
       foreach (['tp_model', 'tp1_close_pct', 'move_sl_to_break_even_on_tp1', 'runner_trailing_enabled', 'runner_trail_distance_rr'] as $field) {
           if ($position->{$field} === null && array_key_exists($field, $policy)) {
               $position->{$field} = $policy[$field];
               $changed = true;
           }
       }

       if ($changed) {
           $position->save();
       }
   }

   protected function targetReached(Position $position, float $price): bool
   {
       if (!$position->target_price) {
           return false;
       }

       return $position->side === 'short'
           ? $price <= (float) $position->target_price
           : $price >= (float) $position->target_price;
   }

   protected function bestPrice(Position $position, float $price): float
   {
       $current = $position->highest_price !== null ? (float) $position->highest_price : $price;
       return $position->side === 'short'
           ? min($current, $price)
           : max($current, $price);
   }

   protected function positionRemainingQty(Position $position): float
   {
       return $position->remaining_qty !== null ? (float) $position->remaining_qty : (float) $position->qty;
   }

   protected function calculateNetPnl(string $side, float $qty, float $entry, float $exit): float
   {
       $direction = strtolower($side) === 'short' ? -1 : 1;
       return ($exit - $entry) * $qty * $direction;
   }

   /**
    * Recompute equity as:
    * start_equity + realized_pnl + unrealized_pnl
    */
   public function recalculateEquity(AiModel $model): void
   {
       $start = (float) ($model->start_equity ?? 10000);
       // Realized PnL = sum of closed trades
       $realized = (float) Trade::where('ai_model_id', $model->id)
                       ->whereNotNull('closed_at')
                       ->sum('net_pnl');
       // Unrealized PnL = open positions at current price
       $unrealized = 0.0;
       $openPositions = Position::where('ai_model_id', $model->id)
           ->where('status', 'open')
           ->get();
       foreach ($openPositions as $p) {
            $marketData = app(\App\Services\MarketData::class);
            $price      = (float) $marketData->getPrice($p->ticker);

           if ($price <= 0) {
               continue;
           }

           $this->applyTakeProfitLogic($p, $price);
           $p->refresh();

           if ($p->status !== 'open') {
               continue;
           }

           $direction = ($p->side === 'short') ? -1 : 1;
           $pnl = ($price - $p->avg_price) * $this->positionRemainingQty($p) * $direction;
           $p->unrealized_pnl = $pnl;
           $p->save();
           $unrealized += $pnl;
       }
       $model->equity = $start + $realized + $unrealized;
       $model->save();
   }

  protected function handleOpenOrder(AiModel $model, array $order): void
  {
    $symbol = $order['symbol'] ?? null;
    $side   = strtoupper($order['side'] ?? 'BUY');
    $qty    = (float) ($order['qty'] ?? 0);
    $stop   = isset($order['stop'])   ? (float)$order['stop']   : null;
    $target = isset($order['target']) ? (float)$order['target'] : null;
    if (!$symbol || $qty <= 0) {
    return;
    }
    // DO NOT block on existing position anymore – we allow adds now.
    $this->openPosition($model, $symbol, $side, $qty, $stop, $target);
  }

  protected function getAllowedSymbols(AiModel $model): array
  {
      // Open positions
      $open = Position::where('ai_model_id', $model->id)
          ->where('status', 'open')
          ->pluck('ticker');

      // Approved daily plan symbols (latest plan)
      $plan = AiDailyPlan::where('ai_model_id', $model->id)
          ->orderByDesc('trade_date')
          ->value('plan_json');

      $approved = collect($plan ?? [])
          ->filter(fn ($s) =>
              is_array($s) &&
              (
                  (!empty($s['approved']) && $s['approved']) ||
                  (($s['status'] ?? null) === 'approved') ||
                  (!empty($s['keep']))
              )
          )
          ->pluck('symbol');

      return $open
          ->merge($approved)
          ->map(fn ($s) => strtoupper($s))
          ->unique()
          ->values()
          ->all();
  }
}

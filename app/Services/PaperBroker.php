<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\Position;
use App\Models\Trade;
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
            'avg_price' => $price,
            'stop_price' => $o['stop'] ?? null,
            'target_price' => $o['target'] ?? null,
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

        $price = (float)($o['exit'] ?? $o['price'] ?? 0);
        if ($price <= 0) $price = $this->feed->last($ticker) ?? 100.0;

        $notionalExit = $pos->qty * $price;
        $pnl = ($pos->side === 'long') ? ($price - $pos->avg_price) * $pos->qty : ($pos->avg_price - $price) * $pos->qty;

        $trade = Trade::where('ai_model_id', $model->id)->where('ticker', $ticker)->whereNull('closed_at')->latest('opened_at')->first();
        if ($trade) {
            $trade->update([
                'exit_price' => $price,
                'notional_exit' => $notionalExit,
                'net_pnl' => $pnl,
                'closed_at' => Carbon::now(),
            ]);
        }

        $pos->update(['status' => 'closed', 'unrealized_pnl' => 0]);
        $this->portfolio->applyClose($model, $notionalExit, $pnl);
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
       $action = $decision['action'] ?? 'HOLD';
       $orders = $decision['orders'] ?? [];
       if (!in_array($action, ['OPEN', 'CLOSE'])) {
           // HOLD: no structural changes, just keep equity as is
           $this->recalculateEquity($model);
           return;
       }
       foreach ($orders as $order) {
           $symbol = $order['symbol'] ?? null;
           $side   = strtoupper($order['side'] ?? 'BUY');
           $qty    = (float) ($order['qty'] ?? 0);
           if (!$symbol || $qty <= 0) {
               continue;
           }
           if ($action === 'OPEN') {
               $this->openPosition($model, $symbol, $side, $qty);
           } elseif ($action === 'CLOSE') {
               $this->closePositionBySymbol($model, $symbol);
           }
       }
       $this->recalculateEquity($model);
   }

   protected function openPosition(AiModel $model, string $symbol, string $side, float $qty): void
   {
       $price = $this->marketData->getPrice($symbol);
       // Either create a new position or add to existing one
       $position = Position::where('ai_model_id', $model->id)
           ->where('ticker', $symbol)
           ->where('status', 'open')
           ->first();
       if ($position) {
           // increase position (simple average price)
           $totalQty   = $position->qty + $qty;
           $newAvg     = ($position->avg_price * $position->qty + $price * $qty) / $totalQty;
           $position->qty       = $totalQty;
           $position->avg_price = $newAvg;
           $position->save();
       } else {
           $position = new Position();
           $position->ai_model_id = $model->id;
           $position->ticker      = $symbol;
           $position->side        = $side;
           $position->qty         = $qty;
           $position->avg_price   = $price;
           $position->status      = 'open';
           $position->opened_at   = now();
           $position->save();
       }
       // log an "open trade" for history
       $trade = new Trade();
       $trade->ai_model_id = $model->id;
       $trade->symbol      = $symbol;
       $trade->side        = $side;
       $trade->qty         = $qty;
       $trade->entry_price = $price;       
       $trade->opened_at   = now();
       $trade->save();
   }

   protected function closePositionBySymbol(AiModel $model, string $symbol): void
   {
       $position = Position::where('ai_model_id', $model->id)
           ->where('ticker', $symbol)
           ->where('status', 'open')
           ->first();
       if (!$position) {
           return;
       }
       $exitPrice = $this->marketData->getPrice($symbol);
       // Close any open trades for that symbol as well
       $openTrades = Trade::where('ai_model_id', $model->id)
                       ->where('symbol', $symbol)
                       ->whereNull('closed_at')
                       ->get();
       foreach ($openTrades as $trade) {
           $netPnl = $this->calculateNetPnl(
               $trade->side,
               $trade->qty,
               $trade->entry_price,
               $exitPrice
           );
           $trade->exit_price = $exitPrice;
           $trade->net_pnl    = $netPnl;           
           $trade->closed_at  = now();
           $trade->save();
       }
       // Mark position as closed
       $position->status       = 'closed';
       $position->closed_at    = now();
       $position->unrealized_pnl = 0;
       $position->save();
   }

   protected function calculateNetPnl(string $side, float $qty, float $entry, float $exit): float
   {
       $direction = strtoupper($side) === 'SELL' ? -1 : 1;
       return ($exit - $entry) * $qty * $direction;
   }

   /**
    * Recompute equity as:
    * start_equity + realized_pnl + unrealized_pnl
    */
   public function recalculateEquity(AiModel $model): void
   {
       $start = (float) ($model->start_equity ?? 0);
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
           $price = $this->marketData->getPrice($p->ticker);
           $direction = strtoupper($p->side) === 'SELL' ? -1 : 1;
           $pnl = ($price - $p->avg_price) * $p->qty * $direction;
           $p->unrealized_pnl = $pnl;
           $p->save();
           $unrealized += $pnl;
       }
       $model->equity = $start + $realized + $unrealized;
       $model->save();
   }
}

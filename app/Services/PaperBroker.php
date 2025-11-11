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
        private PortfolioService $portfolio
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
}

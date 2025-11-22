<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\Position;
use App\Services\PriceFeed\PriceFeedInterface;

class PortfolioService
{
    public function __construct(private PriceFeedInterface $feed) {}

    public function markToMarket(AiModel $model): void
    {
        $open = Position::where('ai_model_id',$model->id)->where('status','open')->get();
        $unreal = 0.0;
        foreach ($open as $p) {
            $last = $this->feed->last($p->ticker) ?? $p->avg_price;
            $pnl = ($p->side === 'long')
                ? ($last - $p->avg_price) * $p->qty
                : ($p->avg_price - $last) * $p->qty;
            $p->unrealized_pnl = $pnl;
            $p->save();
            $unreal += $pnl;
        }
        $cash = (float)($model->cash ?? 0);
        $model->equity = $cash + $unreal;
        $model->save();
    }

    public function applyOpen(AiModel $model, float $notional): void
    {
        $model->cash = (float)($model->cash ?? 0) - $notional;
        $model->save();
    }

    public function applyClose(AiModel $model, float $notional, float $pnl): void
    {
        $model->cash = (float)($model->cash ?? 0) + $notional + $pnl;
        $model->save();
    }
}

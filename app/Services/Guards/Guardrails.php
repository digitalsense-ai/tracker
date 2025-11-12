<?php

namespace App\Services\Guards;

use App\Models\AiModel;
use App\Models\Position;
use App\Models\Trade;
use App\Services\PriceFeed\PriceFeedInterface;

class Guardrails
{
    public function __construct(private PriceFeedInterface $feed) {}

    public function validate(AiModel $model, array $decision): array
    {
        $violations = [];
        $computed   = ['order_notionals'=>[], 'exposure_before'=>0.0, 'exposure_after'=>0.0];

        $equity = (float) ($model->equity ?? 0);
        if ($equity <= 0) $violations[] = 'equity_non_positive';

        // Exposure before
        $open = Position::where('ai_model_id',$model->id)->where('status','open')->get();
        $exposureBefore = 0.0;
        foreach ($open as $p) {
            $last = $this->feed->last($p->ticker) ?? $p->avg_price ?? 0.0;
            $exposureBefore += abs($p->qty * $last);
        }
        $computed['exposure_before'] = $equity > 0 ? $exposureBefore / $equity : 0.0;

        $orders = collect($decision['orders'] ?? []);
        $incomingOpens = $orders->filter(fn($o)=>strtolower($o['type'] ?? '')==='open')->values();

        // Max concurrent
        $maxConcurrent = (int) ($model->max_concurrent_trades ?? 5);
        $openCount = $open->count();
        if ($openCount + $incomingOpens->count() > $maxConcurrent) $violations[]='max_concurrent_trades_exceeded';

        // Re-entry per symbol
        $allowReentry = (bool) ($model->allow_same_symbol_reentry ?? false);
        if (!$allowReentry) {
            $openTickers = $open->pluck('ticker')->unique()->all();
            foreach ($incomingOpens as $o) {
                $t = strtoupper($o['ticker'] ?? '');
                if ($t && in_array($t, $openTickers, true)) $violations[] = "reentry_blocked:{$t}";
            }
        }

        // Cooldown per symbol
        $cooldownMin = (int) ($model->cooldown_minutes ?? 0);
        if ($cooldownMin > 0) {
            foreach ($incomingOpens as $o) {
                $t = strtoupper($o['ticker'] ?? '');
                if (!$t) continue;
                $recent = Trade::where('ai_model_id',$model->id)->where('ticker',$t)->orderByDesc('closed_at')->first();
                if ($recent && $recent->closed_at) {
                    $diff = now()->diffInMinutes($recent->closed_at);
                    if ($diff < $cooldownMin) $violations[] = "cooldown_active:{$t}:{$diff}m<{$cooldownMin}m";
                }
            }
        }

        // Per-trade allocation and leverage caps
        $perTradeAllocPct = (float) ($model->per_trade_alloc_pct ?? 20.0);
        $maxLeverage = (float) ($model->max_leverage ?? 5.0);

        $orderNotionals = [];
        foreach ($incomingOpens as $idx => $o) {
            $t = strtoupper($o['ticker'] ?? '');
            $entry = (float) ($o['entry'] ?? 0.0);
            if ($entry <= 0) $entry = $this->feed->last($t) ?? 0.0;
            $qty = (float) ($o['qty'] ?? 0.0);
            if ($qty <= 0) $qty = 1.0; // placeholder until RiskManager sizes
            $notional = abs($qty * $entry);
            $orderNotionals[$idx] = $notional;

            $cap = max(0.0, $equity * ($perTradeAllocPct / 100.0));
            if ($cap > 0 && $notional > $cap) $violations[] = "per_trade_allocation_exceeded:{$t}";

            $lev = (float) ($o['leverage'] ?? 1.0);
            if ($lev > $maxLeverage) $violations[] = "leverage_exceeded:{$t}:{$lev}>{$maxLeverage}";
        }
        $computed['order_notionals'] = $orderNotionals;

        // Max exposure
        $maxExposurePct = (float) ($model->max_exposure_pct ?? 80.0);
        $incomingExposure = array_sum($orderNotionals);
        $exposureAfter = $exposureBefore + $incomingExposure;
        $computed['exposure_after'] = $equity > 0 ? $exposureAfter / $equity : 0.0;
        $maxAbs = $equity * ($maxExposurePct / 100.0);
        if ($maxExposurePct > 0 && $exposureAfter > $maxAbs) $violations[] = 'max_exposure_exceeded';

        // Max drawdown guard
        $peak = (float) ($model->peak_equity ?? $equity);
        $maxDDPct = (float) ($model->max_drawdown_pct ?? 0.0);
        if ($maxDDPct > 0 && $peak > 0) {
            $threshold = $peak * (1 - $maxDDPct / 100.0);
            if ($equity < $threshold && $incomingOpens->count() > 0) $violations[] = 'max_drawdown_guard_tripped';
        }

        return [empty($violations), $violations, $computed];
    }
}

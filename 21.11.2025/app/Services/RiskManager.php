<?php

namespace App\Services;

use App\Models\AiModel;

class RiskManager
{
    public function size(AiModel $model, array $order): array
    {
        $order['qty'] = (float)($order['qty'] ?? 0);
        if ($order['qty'] > 0) return $order;

        $equity = (float)($model->equity ?? 0);
        $riskPct = (float)($model->risk_pct ?? 0.5);
        $riskAmt = max(0.0, $equity * ($riskPct / 100.0));

        $entry = (float)($order['entry'] ?? 0);
        $stop  = (float)($order['stop'] ?? 0);
        if ($entry <= 0 or $stop <= 0 or $entry == $stop) {
            $order['qty'] = 1;
            return $order;
        }
        $dist = abs($entry - $stop);
        if ($dist <= 0) { $order['qty'] = 1; return $order; }

        $qty = $riskAmt / $dist;
        $order['qty'] = max(1, round($qty, 2));
        return $order;
    }
}

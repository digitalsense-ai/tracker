<?php

namespace App\Services;

class TradeMath
{
    public static function calcLong($entry, $exit, $sl, $positionSize, $feePct, $minFee)
    {
        $shares = $positionSize / $entry;
        $turnover = ($entry + $exit) * $shares;
        $fee = max($minFee, $feePct * $turnover);
        $gross = ($exit - $entry) * $shares;
        $net = $gross - $fee;
        $r = null;
        if ($sl !== null && $entry != $sl) {
            $r = ($exit - $entry) / ($entry - $sl);
        }
        return [
            'shares' => $shares,
            'turnover' => $turnover,
            'fee' => $fee,
            'gross' => $gross,
            'net' => $net,
            'R' => $r,
        ];
    }
}

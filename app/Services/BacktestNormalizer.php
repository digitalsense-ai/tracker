<?php

namespace App\Services;

class BacktestNormalizer
{
    public static function normalize(array $rawResults): array
    {
        return array_map(function ($r) {
            return [
                'ticker' => $r['ticker'] ?? 'N/A',
                'entry_price' => $r['entry_price'] ?? null,
                'exit_price' => $r['exit_price'] ?? ($r['exit'] ?? null),
                'sl_price' => $r['sl_price'] ?? null,
                'tp1' => $r['tp1'] ?? null,
                'tp2' => $r['tp2'] ?? null,
            ];
        }, $rawResults);
    }
}

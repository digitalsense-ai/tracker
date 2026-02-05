<?php

namespace App\Services\Scanner;

use App\Models\SymbolMapping;
use App\Services\MarketData;

class DailyCandidateScanner
{
    public function __construct(private MarketData $marketData) {}

    /**
     * Deterministic scan (NO AI)
     * Returns [symbols, meta]
     */
    public function scan(int $limit): array
    {
        $allow = $this->splitList((string) config('trading.scanner.allow_symbols', ''));
        $deny  = $this->splitList((string) config('trading.scanner.deny_symbols', ''));
        $minP  = config('trading.scanner.min_price');
        $maxP  = config('trading.scanner.max_price');
        $requirePrice = (bool) config('trading.scanner.require_price', true);

        $q = SymbolMapping::query();
        if ((bool) config('trading.scanner.only_enabled_mappings', true)) {
            $q->where('enabled_for_ai', 1);
        }
        $q->orderBy('priority', 'asc');

        $symbols = [];
        $meta = [
            'requested_limit' => $limit,
            'picked' => 0,
            'skipped' => 0,
            'reasons' => [],
        ];

        foreach ($q->cursor() as $row) {
            $sym = strtoupper(trim((string) $row->symbol));
            if ($sym === '') { continue; }

            if (!empty($allow) && !in_array($sym, $allow, true)) {
                $meta['skipped']++;
                $meta['reasons']['not_in_allowlist'] = ($meta['reasons']['not_in_allowlist'] ?? 0) + 1;
                continue;
            }
            if (in_array($sym, $deny, true)) {
                $meta['skipped']++;
                $meta['reasons']['in_denylist'] = ($meta['reasons']['in_denylist'] ?? 0) + 1;
                continue;
            }

            $price = null;
            if ($requirePrice || $minP !== null || $maxP !== null) {
                $price = $this->marketData->getPrice($sym);
            }

            if ($requirePrice && $price === null) {
                $meta['skipped']++;
                $meta['reasons']['no_price'] = ($meta['reasons']['no_price'] ?? 0) + 1;
                continue;
            }
            if ($price !== null) {
                if ($minP !== null && (float)$price < (float)$minP) {
                    $meta['skipped']++;
                    $meta['reasons']['below_min_price'] = ($meta['reasons']['below_min_price'] ?? 0) + 1;
                    continue;
                }
                if ($maxP !== null && (float)$price > (float)$maxP) {
                    $meta['skipped']++;
                    $meta['reasons']['above_max_price'] = ($meta['reasons']['above_max_price'] ?? 0) + 1;
                    continue;
                }
            }

            $symbols[] = $sym;
            $meta['picked']++;

            if (count($symbols) >= $limit) {
                break;
            }
        }

        return [$symbols, $meta];
    }

    private function splitList(string $csv): array
    {
        $csv = trim($csv);
        if ($csv === '') return [];
        $parts = array_map('trim', explode(',', $csv));
        $parts = array_filter($parts, fn($x) => $x !== '');
        $parts = array_map(fn($x) => strtoupper($x), $parts);
        return array_values(array_unique($parts));
    }
}

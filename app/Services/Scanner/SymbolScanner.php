<?php

namespace App\Services\Scanner;

use App\Models\SymbolMapping;

class SymbolScanner
{
    /**
     * Return a deterministic list of candidate symbols (no AI).
     */
    public function candidates(int $limit = 20): array
    {
        return SymbolMapping::query()
            ->where('enabled_for_ai', 1)
            ->orderBy('priority', 'asc')
            ->limit($limit)
            ->pluck('symbol')
            ->map(fn ($s) => strtoupper(trim($s)))
            ->unique()
            ->values()
            ->all();
    }
}

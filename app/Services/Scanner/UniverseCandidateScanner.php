<?php

namespace App\Services\Scanner;

use App\Models\SaxoInstrument;
use App\Models\SymbolMapping;
use Illuminate\Support\Facades\Log;

class UniverseCandidateScanner
{
    /**
     * Build a candidate symbol list from the full Saxo universe (saxo_instruments),
     * and ensure symbol_mappings rows exist for the chosen candidates.
     *
     * v1 rules:
     * - AssetType = Stock (configurable)
     * - ExchangeId in allow list (configurable)
     * - Deterministic ordering by symbol, then limit
     */
    public function candidates(int $limit = 25, array $assetTypes = ['Stock'], array $exchangeIds = ['NASDAQ','NYSE']): array
    {
        $assetTypes = array_values(array_filter(array_map('trim', $assetTypes)));
        $exchangeIds= array_values(array_filter(array_map('trim', $exchangeIds)));

        // Pull a bit more than limit to handle duplicates after normalization.
        $rows = SaxoInstrument::query()
            ->whereIn('asset_type', $assetTypes)
            ->whereIn('exchange_id', $exchangeIds)
            ->where('is_tradable', true)
            ->orderBy('symbol', 'asc')
            ->limit($limit * 5)
            ->get(['uic','asset_type','symbol','exchange_id']);

        $out = [];
        $seen = [];

        // Prefer earlier exchanges in the list if duplicates exist.
        $exchangeRank = array_flip($exchangeIds);

        foreach ($rows as $r) {
            $full = strtoupper(trim($r->symbol));
            $base = strtoupper(trim(explode(':', $full)[0])); // AAPL:xnas -> AAPL

            if ($base === '' || isset($seen[$base])) {
                continue;
            }

            $seen[$base] = true;

            // Ensure mapping exists (this is what allows MarketData->getPrice() to work)
            $mapping = SymbolMapping::query()->where('symbol', $base)->first();
            if (!$mapping) {
                try {
                    SymbolMapping::create([
                        'symbol' => $base,
                        'saxo_uic' => (int)$r->uic,
                        'saxo_asset_type' => $r->asset_type,
                        'enabled_for_ai' => 1,
                        'priority' => 999, // default low priority; admin can adjust
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('SymbolMapping create failed', [
                        'symbol' => $base,
                        'uic' => $r->uic,
                        'err' => $e->getMessage(),
                    ]);
                    continue;
                }
            } else {
                // If mapping exists but missing fields, backfill
                $dirty = false;
                if (!$mapping->saxo_uic) { $mapping->saxo_uic = (int)$r->uic; $dirty = true; }
                if (!$mapping->saxo_asset_type) { $mapping->saxo_asset_type = $r->asset_type; $dirty = true; }
                if ($mapping->enabled_for_ai === null) { $mapping->enabled_for_ai = 1; $dirty = true; }
                if ($dirty) $mapping->save();
            }

            $out[] = $base;

            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }
}

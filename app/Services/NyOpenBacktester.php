<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;

class NyOpenBacktester
{
    public function runProfileOnTickerWindow(
        int $profileId,
        string $ticker,
        CarbonImmutable $startEt,
        CarbonImmutable $endEt
    ): array {
        $trades = [];

        Log::info('NYOPEN service.in', [
            'profile_id' => $profileId,
            'ticker'     => $ticker,
            'start'      => $startEt->toIso8601String(),
            'end'        => $endEt->toIso8601String(),
        ]);

        // TODO: Hent jeres data her (bars, prev_close osv.)
        $barsCount   = $barsCount   ?? null;
        $prevClose   = $prevClose   ?? null;
        $gapThreshold = $gapThreshold ?? null;
        $minVolume    = $minVolume ?? null;
        $candidates   = $candidates ?? [];
        $entries      = $entries ?? [];
        $exits        = $exits ?? [];

        Log::info('NYOPEN strat.data', [
            'profile_id' => $profileId,
            'ticker'     => $ticker,
            'bars_in_window' => $barsCount,
            'has_prev_close' => isset($prevClose),
        ]);

        Log::info('NYOPEN strat.candidates', [
            'profile_id' => $profileId,
            'ticker'     => $ticker,
            'candidates_raw' => is_countable($candidates) ? count($candidates) : 0,
            'gap_threshold'  => $gapThreshold,
            'min_volume'     => $minVolume,
        ]);

        // TODO: indsæt jeres logik til at fylde $trades baseret på $candidates
        // fx: $trades = $entries; // eller kombiner entries/exits

        Log::info('NYOPEN strat.result', [
            'profile_id' => $profileId,
            'ticker'     => $ticker,
            'entries'    => is_countable($entries) ? count($entries) : 0,
            'exits'      => is_countable($exits) ? count($exits) : 0,
            'trades'     => is_countable($trades) ? count($trades) : 0,
        ]);

        Log::info('NYOPEN service.out', [
            'profile_id' => $profileId,
            'ticker'     => $ticker,
            'n'          => is_countable($trades) ? count($trades) : 0,
        ]);

        return $trades;
    }
}

<?php

namespace App\Services;

use Carbon\CarbonImmutable;

/**
 * Plug your actual strategy here.
 * Return an array of trades for a given profile & ticker & time window.
 */
class NyOpenBacktester
{
    /**
     * @param int $profileId
     * @param string $ticker
     * @param CarbonImmutable $startEt  // America/New_York
     * @param CarbonImmutable $endEt    // America/New_York
     * @return array<int, array{entry_price:float, exit_price:float, created_at:string, closed_at:?string, forecast_type?:string}>
     */
    public function runProfileOnTickerWindow(int $profileId, string $ticker, CarbonImmutable $startEt, CarbonImmutable $endEt): array
    {
        // TODO: Integrér jeres strategi.
        // Returnér trades som arrays med UTC-tidsstempler (ISO-8601), fx:
        // return [[
        //   'entry_price' => 100.0,
        //   'exit_price' => 105.0,
        //   'created_at' => '2025-08-01T13:45:00Z',
        //   'closed_at'  => '2025-08-01T14:25:00Z',
        //   'forecast_type' => 'gap-up',
        // ]];
        return [];
    }
}

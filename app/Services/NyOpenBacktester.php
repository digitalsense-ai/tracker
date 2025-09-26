<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use App\Services\Data\PriceDataProvider;

class NyOpenBacktester
{
    public function __construct(private PriceDataProvider $data) {}

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

        $prevClose = $this->data->getPrevClose($ticker, $startEt);
        $barsPack  = $this->data->getBarsInWindow($ticker, $startEt, $endEt);
        $barsCount = $barsPack['count'] ?? 0;

        Log::info('NYOPEN strat.data', [
            'profile_id'     => $profileId,
            'ticker'         => $ticker,
            'bars_in_window' => $barsCount,
            'has_prev_close' => $prevClose !== null,
        ]);

        Log::info('NYOPEN service.out', [
            'profile_id' => $profileId,
            'ticker'     => $ticker,
            'n'          => count($trades),
        ]);

        return $trades;
    }
}

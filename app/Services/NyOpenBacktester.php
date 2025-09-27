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

        $btFile = (new \ReflectionClass($this))->getFileName();
        Log::info('NYOPEN class.backtester', ['class'=>get_class($this), 'file'=>$btFile]);

        $provFile = (new \ReflectionClass($this->data))->getFileName();
        Log::info('NYOPEN class.provider', ['class'=>get_class($this->data), 'file'=>$provFile]);

        Log::info('NYOPEN service.in', [
            'profile_id' => $profileId,
            'ticker'     => $ticker,
            'start'      => $startEt->toIso8601String(),
            'end'        => $endEt->toIso8601String(),
        ]);

        Log::info('NYOPEN strat.call_bars', [
            'ticker'  => $ticker,
            'startEt' => $startEt->toIso8601String(),
            'endEt'   => $endEt->toIso8601String(),
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

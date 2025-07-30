<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Stock;

class ForecastScanCommand extends Command
{
    protected $signature = 'forecast:scan';
    protected $description = 'Scan tickers from Nordnet list and fetch forecast metrics (GAP %, RVOL, Volume)';

    public function handle()
    {
        $this->info('Starting forecast scan...');

        //$tickers = json_decode(file_get_contents(base_path('nordnet_tickers.json')));
        $tickers = json_decode(file_get_contents(storage_path('app/nordnet_tickers.json')));
        $apiKey = config('services.finnhub.key');
        $minGap = config('forecast.min_gap');
        $minRvol = config('forecast.min_rvol');
        $minVolume = config('forecast.min_volume');

        foreach ($tickers as $ticker) {
            // 1. Hent quote data
            $quoteUrl = "https://finnhub.io/api/v1/quote?symbol={$ticker}&token={$apiKey}";
            $res = Http::get($quoteUrl);
            if (!$res->successful()) continue;

            $data = $res->json();
            $prevClose = $data['pc'];
            $current = $data['c'];
            $volume = $data['v'] ?? 0;
            if (!$prevClose || !$current) continue;

            // 2. Beregn GAP
            $gapPercent = (($current - $prevClose) / $prevClose) * 100;

            // 3. Dummy RVOL til placeholder (kræver historik for rvol)
            $rvol = 1 + (rand(0, 10) / 10); // fx 1.0 til 2.0

            // 4. Valider mod forecast krav
            if ($gapPercent >= $minGap && $rvol >= $minRvol && $volume >= $minVolume) {
                Stock::updateOrCreate([
                    'ticker' => $ticker,
                ], [
                    'gap' => round($gapPercent, 2),
                    'rvol' => $rvol,
                    'volume' => $volume,
                    'status' => 'forecast',
                    'forecast' => 'gap-up',
                ]);

                $this->info("[✔] {$ticker} - GAP: {$gapPercent}% | RVOL: {$rvol} | Volume: {$volume}");
            } else {
                $this->line("[--] {$ticker} - does not meet criteria.");
            }
        }

        $this->info('Forecast scan complete.');
    }
}

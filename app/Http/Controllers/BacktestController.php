<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BacktestController extends Controller
{
    public function index()
    {
        $apiKey = config('services.finnhub.key');

        // Liste over tickers vi vil scanne
        $tickers = ['AAPL', 'MSFT', 'TSLA', 'NVDA', 'AMD'];
        $results = [];

        // Tidsvindue: de sidste 5 handelsdage
        $end = now()->timestamp;
        $start = now()->subDays(5)->timestamp;

        // Forecast filterkrav
        $minGap = 3.0;
        $minRvol = 1.5;
        $minVolume = 1000000;

        foreach ($tickers as $ticker) {
            // Hent candles fra Finnhub
            $url = "https://finnhub.io/api/v1/stock/candle";
            $response = Http::get($url, [
                'symbol' => $ticker,
                'resolution' => 'D',
                'from' => $start,
                'to' => $end,
                'token' => $apiKey,
            ]);

            if (!$response->ok() || $response['s'] !== 'ok') {
                Log::warning("Finnhub API error for $ticker");
                continue;
            }

            $data = $response->json();
            $count = count($data['c']);
            if ($count < 2) continue;

            // Brug seneste og næstseneste candle
            $prevClose = $data['c'][$count - 2];
            $open = $data['o'][$count - 1];
            $volume = $data['v'][$count - 1];

            // Dummy RVOL (kan opdateres senere)
            $rvol = round(rand(15, 30) / 10, 2);

            // Udregn GAP %
            $gap = round((($open - $prevClose) / $prevClose) * 100, 2);

            // Tjek forecast-kriterier
            if ($gap >= $minGap && $rvol >= $minRvol && $volume >= $minVolume) {
                $results[] = [
                    'ticker' => $ticker,
                    'date' => now()->toDateString(),
                    'gap' => $gap,
                    'rvol' => $rvol,
                    'volume' => $volume,
                    'forecast_type' => 'gap-up',
                    'status' => 'forecast',
                ];
            }
        }

        return view('backtest', ['results' => $results]);
    }
}

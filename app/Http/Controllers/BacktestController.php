<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BacktestController extends Controller
{
    public function index()
    {
        $tickersPath = storage_path('app/nordnet_tickers.json');
        if (!file_exists($tickersPath)) {
            return view('backtest', ['results' => [], 'error' => 'Ticker list not found.']);
        }

        $tickers = json_decode(file_get_contents($tickersPath), true);
        $apiKey = config('services.finnhub.key');
        $results = [];

        foreach ($tickers as $ticker) {
            for ($i = 0; $i < 5; $i++) {
                $date = now()->subDays($i)->format('Y-m-d');
                try {
                    // Simuleret gap og volume
                    $openPrice = rand(80, 120);
                    $prevClose = rand(80, 120);
                    $gap = round(($openPrice - $prevClose) / $prevClose * 100, 2);
                    $volume = rand(500000, 5000000);

                    if ($gap >= 3.0 && $volume >= 1000000) {
                        $results[] = [
                            'ticker' => $ticker,
                            'gap' => $gap,
                            'volume' => $volume,
                            'date' => $date,
                            'status' => 'forecast'
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error("Backtest failed for $ticker on $date: " . $e->getMessage());
                }
            }
        }

        return view('backtest', ['results' => $results, 'error' => null]);
    }
}

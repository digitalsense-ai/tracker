
<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BacktestController extends Controller
{
    public function index()
    {
        $tickers = ['AAPL', 'MSFT', 'TSLA', 'NVDA', 'AMD'];
        $results = [];

        foreach ($tickers as $ticker) {
            $url = 'https://finnhub.io/api/v1/stock/candle';
            $params = [
                'symbol' => $ticker,
                'resolution' => '5',
                'from' => strtotime('-5 days'),
                'to' => time(),
                'token' => env('FINNHUB_API_KEY')
            ];

            $response = Http::get($url, $params);

            if ($response->successful() && $response->json('s') === 'ok') {
                $candles = $response->json();
                $results[] = [
                    'ticker' => $ticker,
                    'status' => 'simulated',
                    'entries' => count($candles['c'] ?? []),
                    'close_price' => end($candles['c']),
                    'gap_check' => round(rand(1, 5) + 1.5, 2),
                    'rvol' => round(rand(10, 30) / 10, 2),
                    'volume' => array_sum($candles['v'] ?? [])
                ];
            }
        }

        return view('backtest', ['results' => $results]);
    }
}

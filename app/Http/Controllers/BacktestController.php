<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BacktestController extends Controller
{
    public function index()
    {
        // Define test tickers
        $tickers = ['AAPL', 'MSFT', 'TSLA'];
        $results = [];

        // Define test settings
        $minGap = 3.0;
        $minRvol = 1.5;
        $minVolume = 1000000;

        foreach ($tickers as $ticker) {
            // Fallback dummy data
            $result = [
                'ticker' => $ticker,
                'date' => now()->toDateString(),
                'gap' => rand(2, 6), // simulate 2–6% gap
                'rvol' => rand(10, 30) / 10, // simulate 1.0–3.0 rvol
                'volume' => rand(1000000, 5000000),
                'forecast_type' => 'gap-up',
                'status' => 'forecast',
            ];

            // Apply filters manually
            if (
                $result['gap'] >= $minGap &&
                $result['rvol'] >= $minRvol &&
                $result['volume'] >= $minVolume
            ) {
                $results[] = $result;
            }
        }

        return view('backtest', ['results' => $results]);
    }
}

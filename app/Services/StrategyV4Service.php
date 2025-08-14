<?php

namespace App\Services;

use App\Models\SimulatedTrade;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StrategyV4Service
{
    public function runBacktest(array $tickers, string $date)
    {
        $results = [];

        foreach ($tickers as $ticker) {
            $symbol = $ticker;
            Log::info("Running V4 backtest for {$symbol} on {$date}");

            $from = strtotime("$date 09:00:00");
            $to = strtotime("$date 17:30:00");
            $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?interval=1m&period1={$from}&period2={$to}&includePrePost=false";

            $response = Http::get($url);
            if (!$response->successful()) {
                Log::error("Yahoo request failed for {$symbol}");
                continue;
            }

            $data = $response->json();
            $candles = $data['chart']['result'][0]['indicators']['quote'][0] ?? null;
            if (!$candles) {
                continue;
            }

            $high = max(array_slice($candles['high'], 0, 15));
            $low = min(array_slice($candles['low'], 0, 15));
            $entryIndex = null;

            for ($i = 15; $i < count($candles['close']); $i++) {
                if ($candles['low'][$i] <= $high && $candles['close'][$i] > $high) {
                    $entryIndex = $i;
                    break;
                }
            }

            if ($entryIndex) {
                $entryPrice = $candles['close'][$entryIndex];
                $slPrice = $low;
                $tpPrice = $entryPrice + ($entryPrice - $slPrice) * 2;
                $status = 'open';

                for ($j = $entryIndex + 1; $j < count($candles['close']); $j++) {
                    $price = $candles['close'][$j];
                    if ($price <= $slPrice) {
                        $status = 'SL';
                        break;
                    } elseif ($price >= $tpPrice) {
                        $status = 'TP';
                        break;
                    }
                }

                SimulatedTrade::create([
                    'ticker' => $symbol,
                    'date' => $date,
                    'entry_price' => $entryPrice,
                    'sl_price' => $slPrice,
                    'tp1' => $tpPrice,
                    'tp2' => null,
                    'status' => $status,
                ]);

                $results[] = compact('symbol', 'entryPrice', 'slPrice', 'tpPrice', 'status');
            }
        }

        return $results;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\SimulatedTrade;
use App\Models\Stock;
use Carbon\Carbon;

class BacktestService
{
    public function simulate()
    {
        $results = [];
        $stocks = Stock::all();

        foreach ($stocks as $stock) {
            $ticker = $stock->ticker;
            $date = Carbon::now()->subDays(1)->format('Y-m-d');

            // Yahoo Finance API via rapidapi.com or public endpoint
            $from = strtotime("$date 15:30:00");
            $to = strtotime("$date 17:30:00");
            $yahooSymbol = $ticker; // US tickers: no suffix. (DK: ".CO", SE: ".ST", etc.)

            Log::info("Backtest: {$ticker} -> symbol={$yahooSymbol}");
            
            $response = Http::get("https://query1.finance.yahoo.com/v8/finance/chart/{$yahooSymbol}?interval=1m&period1={$from}&period2={$to}&includePrePost=false");

            if ($response->successful()) {
                $data = $response->json();
                $candles = $data['chart']['result'][0]['indicators']['quote'][0] ?? null;

                if (!$candles || empty($candles['close'])) {
                    continue;
                }

                $ohlc = [
                    'c' => $candles['close'],
                    'h' => $candles['high'],
                    'l' => $candles['low']
                ];

                $high = max(array_slice($ohlc['h'], 0, 15));
                $low = min(array_slice($ohlc['l'], 0, 15));

                $entryIndex = null;
                for ($i = 15; $i < count($ohlc['c']); $i++) {
                    if ($ohlc['l'][$i] <= $high && $ohlc['c'][$i] > $high) {
                        $entryIndex = $i;
                        break;
                    }
                }

                if ($entryIndex) {
                    $entryPrice = $ohlc['c'][$entryIndex];
                    $slPrice = $low;
                    $tp1 = $entryPrice + ($entryPrice - $slPrice);
                    $tp2 = $entryPrice + 2 * ($entryPrice - $slPrice);

                    $status = 'open';
                    for ($j = $entryIndex + 1; $j < count($ohlc['c']); $j++) {
                        $price = $ohlc['c'][$j];
                        if ($price <= $slPrice) {
                            $status = 'SL';
                            break;
                        } elseif ($price >= $tp2) {
                            $status = 'TP2';
                            break;
                        } elseif ($price >= $tp1) {
                            $status = 'TP1';
                        }
                    }

                    SimulatedTrade::create([
                        'ticker' => $ticker,
                        'date' => $date,
                        'entry_price' => $entryPrice,
                        'sl_price' => $slPrice,
                        'tp1' => $tp1,
                        'tp2' => $tp2,
                        'status' => $status,
                    ]);

                    $results[] = compact('ticker', 'entryPrice', 'slPrice', 'tp1', 'tp2', 'status');
                }
            }
        }

        return $results;
    }
}

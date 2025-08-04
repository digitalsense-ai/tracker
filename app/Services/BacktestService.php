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

            $response = Http::withToken(config('services.finnhub.key'))
                ->get("https://finnhub.io/api/v1/stock/candle", [
                    'symbol' => $ticker,
                    'resolution' => '1',
                    'from' => strtotime("$date 15:30:00"),
                    'to' => strtotime("$date 17:30:00"),
                ]);

            if ($response->successful() && $response['s'] === 'ok') {
                $ohlc = $response->json();

                // find ORB High/Low from first 15m candles
                $high = max(array_slice($ohlc['h'], 0, 15));
                $low = min(array_slice($ohlc['l'], 0, 15));

                // simulate retest entry (long above high)
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

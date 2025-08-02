<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BacktestService
{
    public function run()
    {
        $tickersPath = storage_path('app/nordnet_tickers.json');
        if (!file_exists($tickersPath)) {
            Log::warning("Ticker list not found for backtest.");
            return;
        }

        $tickers = json_decode(file_get_contents($tickersPath), true);

        foreach ($tickers as $ticker) {
            for ($i = 0; $i < 5; $i++) {
                $date = now()->subDays($i)->format('Y-m-d');

                // Simulate prices
                $entry = rand(90, 110);
                $sl     = $entry - rand(1, 5);
                $tp1    = $entry + rand(1, 3);
                $tp2    = $entry + rand(4, 6);
                $tp3    = $entry + rand(7, 10);

                // Randomly decide which level is hit first
                $outcome = collect(['tp1', 'tp2', 'tp3', 'sl'])->random();
                $exitPrice = match($outcome) {
                    'tp1' => $tp1,
                    'tp2' => $tp2,
                    'tp3' => $tp3,
                    'sl'  => $sl,
                };

                // Simulate PnL from 1000 investment
                $investment = 1000;
                $pctChange = ($exitPrice - $entry) / $entry;
                $resultAmount = round($investment + $investment * $pctChange, 2);
                $isWin = in_array($outcome, ['tp1', 'tp2', 'tp3']);

                DB::table('simulated_trades')->insert([
                    'ticker' => $ticker,
                    'date' => $date,
                    'entry_price' => $entry,
                    'sl_price' => $sl,
                    'tp1_price' => $tp1,
                    'tp2_price' => $tp2,
                    'tp3_price' => $tp3,
                    'exit_price' => $exitPrice,
                    'exit_type' => $outcome,
                    'is_win' => $isWin,
                    'pnl_amount' => $resultAmount,
                    'status' => 'simulated',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}

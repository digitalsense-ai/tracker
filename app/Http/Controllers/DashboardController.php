<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock;
use App\Models\Trade;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Dummy forecastSettings
        $forecastSettings = [
            'min_gap' => 3,
            'min_rvol' => 1.5,
            'min_volume' => 2000000,
            'forecast_type' => 'gap-up',
        ];

        // Dummy stock data (in real setup, replace with DB fetch)
        $stocks = collect([
            (object)[
                'ticker' => 'AAPL',
                'gap' => 3.2,
                'rvol' => 2.1,
                'volume' => 2500000,
                'status' => 'entry',
                'forecast' => 'gap-up'
            ],
            (object)[
                'ticker' => 'MSFT',
                'gap' => 3.5,
                'rvol' => 2.5,
                'volume' => 3500000,
                'status' => 'exit',
                'forecast' => 'gap-up'
            ],
        ]);

        // Status update logic and simulated trade logging
        foreach ($stocks as $stock) {
            if ($stock->status === 'forecast' && $stock->gap > 3 && $stock->rvol > 1.5) {
                $stock->status = 'breakout';
            } elseif ($stock->status === 'breakout' && $stock->volume > 3000000) {
                $stock->status = 'retest';
            } elseif ($stock->status === 'retest' && $stock->gap > 2.5) {
                $stock->status = 'entry';
            } elseif ($stock->status === 'entry' && $stock->rvol > 2) {
                $stock->status = 'exit';
            }

            // If stock has reached exit, log it as a trade if not already saved
            if ($stock->status === 'exit') {
                $existing = Trade::where('ticker', $stock->ticker)
                                ->whereDate('date', Carbon::today())
                                ->first();

                if (!$existing) {
                    Trade::create([
                        'ticker' => $stock->ticker,
                        'date' => Carbon::today(),
                        'entry_price' => 100.00,
                        'exit_price' => 105.00,
                        'stop_loss' => 97.50,
                        'result' => 'win',
                        'forecast_type' => $stock->forecast,
                    ]);
                }
            }
        }

        return view('dashboard', [
            'stocks' => $stocks,
            'forecastSettings' => $forecastSettings,
        ]);
    }
}

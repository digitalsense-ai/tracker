
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trade;

class TradeResultController extends Controller
{
    public function index()
    {
        // Dummy trades until DB is fully populated
        $trades = collect([
            (object)[
                'ticker' => 'AAPL',
                'date' => '2024-07-18',
                'entry_price' => 182.45,
                'exit_price' => 185.00,
                'stop_loss' => 180.00,
                'result' => 'win',
                'forecast_type' => 'gap-up',
            ],
            (object)[
                'ticker' => 'TSLA',
                'date' => '2024-07-18',
                'entry_price' => 690.10,
                'exit_price' => 675.00,
                'stop_loss' => 680.00,
                'result' => 'loss',
                'forecast_type' => 'volatility-squeeze',
            ],
        ]);

        return view('results', compact('trades'));
    }
}

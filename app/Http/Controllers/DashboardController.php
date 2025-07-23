
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock;

class DashboardController extends Controller
{
    public function index()
    {
        // Dummy stock data for display
        $stocks = collect([
            (object)[
                'ticker' => 'AAPL', 'gap' => 4.2, 'rvol' => 1.6,
                'volume' => 2500000, 'status' => 'forecast', 'forecast' => 'gap-up'
            ],
            (object)[
                'ticker' => 'TSLA', 'gap' => 2.8, 'rvol' => 1.7,
                'volume' => 3500000, 'status' => 'forecast', 'forecast' => 'gap-up'
            ],
            (object)[
                'ticker' => 'AMZN', 'gap' => 3.5, 'rvol' => 1.4,
                'volume' => 1900000, 'status' => 'forecast', 'forecast' => 'gap-down'
            ],
        ]);

        return view('dashboard', compact('stocks'));
    }

    public function updateForecastConfig(Request $request)
    {
        $validated = $request->validate([
            'min_gap' => 'required|numeric',
            'min_rvol' => 'required|numeric',
            'min_volume' => 'required|integer',
            'forecast_type' => 'required|string',
        ]);

        session(['forecast_config' => $validated]);

        return redirect('/dashboard')->with('success', 'Forecast settings updated!');
    }
}

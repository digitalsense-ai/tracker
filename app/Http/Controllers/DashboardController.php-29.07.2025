<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\YahooStockService;

class DashboardController extends Controller
{
    public function index()
    {
        $forecastSettings = session('forecast_config', [
            'min_gap' => 3,
            'min_rvol' => 1.5,
            'min_volume' => 2000000,
            'forecast_type' => 'gap-up',
        ]);

        $stocks = collect(YahooStockService::getLiveForecastStocks($forecastSettings));

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

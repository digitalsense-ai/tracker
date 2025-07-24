<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock;

class DashboardController extends Controller
{
    public function index()
    {
        // Hent alle stocks fra databasen
        $stocks = Stock::all();

        // Simuleret forecast settings (kan udvides med DB senere)
        $forecastSettings = [
            'min_gap' => 3,
            'min_rvol' => 1.5,
            'min_volume' => 2000000,
            'forecast_type' => 'gap-up',
        ];

        return view('dashboard', [
            'stocks' => $stocks,
            'forecastSettings' => $forecastSettings,
        ]);
    }

    public function updateForecastConfig(Request $request)
    {
        // TODO: Gem settings i DB, for nu redirect
        return redirect('/dashboard')->with('status', 'Forecast settings updated!');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        // Brug dummy aktier indtil database er klar
        $stocks = collect([
            (object)[
                'ticker' => 'AAPL',
                'gap' => 4.2,
                'rvol' => 2.1,
                'volume' => 3500000,
                'forecast' => 'gap-up',
                'status' => 'forecast'
            ],
            (object)[
                'ticker' => 'TSLA',
                'gap' => 5.5,
                'rvol' => 1.7,
                'volume' => 4200000,
                'forecast' => 'gap-up',
                'status' => 'forecast'
            ],
            (object)[
                'ticker' => 'NVDA',
                'gap' => 2.9,
                'rvol' => 2.3,
                'volume' => 2500000,
                'forecast' => 'gap-up',
                'status' => 'forecast'
            ],
        ]);

        return view('dashboard', compact('stocks'));
    }
}

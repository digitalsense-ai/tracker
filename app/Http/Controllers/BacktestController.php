<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BacktestNormalizer;

class BacktestController extends Controller
{
    public function index()
    {
        $rawResults = [
            ['ticker' => 'AAPL', 'entry_price' => 100, 'exit' => 110],
            ['ticker' => 'MSFT', 'entry_price' => 200, 'exit_price' => 195]
        ];

        $results = BacktestNormalizer::normalize($rawResults);

        return view('backtest', ['results' => $results]);
    }
}

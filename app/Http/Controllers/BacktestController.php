<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BacktestService;

class BacktestController extends Controller
{
    public function index(Request $request, BacktestService $backtestService)
    {
        $results = $backtestService->simulate();
        return view('backtest', ['results' => $results, 'error' => null]);
    }
}

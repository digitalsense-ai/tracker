<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use App\Models\Trade;
use App\Models\SimulatedTrade;

class TradeResultController extends Controller
{
    public function index()
    {
        //$trades = Trade::orderBy('date', 'desc')->get();
        $trades = SimulatedTrade::orderBy('created_at', 'desc')->limit(200)->get();
        return view('results', compact('trades'));
    }
}

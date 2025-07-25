<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trade;

class TradeResultController extends Controller
{
    public function index()
    {
        $trades = Trade::orderBy('date', 'desc')->get();
        return view('results', compact('trades'));
    }
}

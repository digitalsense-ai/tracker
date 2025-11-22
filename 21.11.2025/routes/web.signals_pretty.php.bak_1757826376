<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\SimulatedTrade;

// Pretty-print signals (HTML table instead of raw JSON)
Route::get('/signals/pretty', function () {
    $signals = SimulatedTrade::orderBy('date', 'desc')->limit(100)->get();

    return view('signals.pretty', [
        'signals' => $signals,
    ]);
})->name('signals.pretty');

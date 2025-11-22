<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/signals', function(){
    return view('signals.index');
})->name('signals.index');

Route::get('/signals/json', function(){
    $rows = DB::table('simulated_trades')->orderByDesc('date')->limit(50)->get();
    return response()->json($rows);
})->name('signals.json');

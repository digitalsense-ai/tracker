<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/profiles/diag', function(){
    $sp = DB::table('strategy_profiles')->count();
    $hasSim = DB::getSchemaBuilder()->hasTable('simulated_trades');
    $simSample = $hasSim ? DB::table('simulated_trades')->orderByDesc('date')->limit(3)->get() : [];
    $hasRes = DB::getSchemaBuilder()->hasTable('profile_results');
    $resTail = $hasRes ? DB::table('profile_results')->orderByDesc('id')->limit(5)->get() : [];
    return response()->json([
        'strategy_profiles_total' => $sp,
        'has_simulated_trades_table' => $hasSim,
        'simulated_trades_sample' => $simSample,
        'has_profile_results' => $hasRes,
        'profile_results_tail' => $resTail,
    ]);
})->name('profiles.diag');

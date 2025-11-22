<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Route::get('/profiles/tools', function() {
    return view('profiles.run');
})->name('profiles.tools');

Route::post('/profiles/tools/run', function(Request $request) {
    $days = $request->input('days', 5);
    $limit = $request->input('limit', 50);
    $profile = $request->input('profile');

    $output = "";
    $commands = [
        "profiles:backtest",
        "profiles:baseline",
        "profiles:backtest-hotfix",
        "profiles:diag",
    ];

    foreach ($commands as $cmd) {
        try {
            $params = ["--days={$days}", "--limit={$limit}"];
            if ($profile) $params[] = "--profile={$profile}";
            Artisan::call($cmd, $params);
            $output .= "Ran {$cmd}\n".Artisan::output()."\n";
            break;
        } catch (Exception $e) {
            $output .= "Failed {$cmd}: ".$e->getMessage()."\n";
        }
    }

    $counts = [
        'profiles' => DB::table('strategy_profiles')->count(),
        'results' => DB::table('profile_results')->count(),
        'trades' => DB::table('simulated_trades')->count(),
    ];

    return response()->view('profiles.run', [
        'output' => $output,
        'counts' => $counts,
    ]);
})->name('profiles.tools.run');

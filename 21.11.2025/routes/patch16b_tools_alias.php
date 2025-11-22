<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\BufferedOutput;

// === patch16B alias (fixed) ===
Route::get('/_profiles_tools', function () {
    return view('profiles.run', [
        'defaults' => [
            'days'   => request('days', 10),
            'limit'  => request('limit', 50),
            'profile'=> request('profile', ''),
        ],
    ]);
})->name('profiles.tools.alt');

Route::post('/_profiles_tools/run', function () {
    $r = request();
    $days    = (int) $r->input('days', 10);
    $limit   = (int) $r->input('limit', 50);
    $profile = $r->input('profile');

    $outputs = [];

    $candidates = [
        ['cmd' => 'profiles:backtest',        'opts' => ['--days' => $days, '--limit' => $limit, '-vv' => true]],
        ['cmd' => 'profiles:baseline',        'opts' => ['--days' => $days, '--limit' => $limit, '-vv' => true]],
        ['cmd' => 'profiles:backtest-hotfix', 'opts' => ['--days' => $days, '--limit' => $limit, '-vv' => true]],
        ['cmd' => 'profiles:diag',            'opts' => []],
    ];

    if ($profile !== null && $profile !== '') {
        foreach ($candidates as &$c) {
            $c['opts']['--profile'] = $profile;
        }
        unset($c);
    }

    foreach ($candidates as $c) {
        try {
            $buffer = new BufferedOutput;
            Artisan::call($c['cmd'], $c['opts'], $buffer);
            $out = $buffer->fetch();
            $outputs[] = [
                'command' => $c['cmd'],
                'options' => $c['opts'],
                'output'  => $out,
            ];
            if (trim($out) !== '') { break; }
        } catch (\Throwable $e) {
            $outputs[] = [
                'command' => $c['cmd'],
                'options' => $c['opts'],
                'error'   => $e->getMessage(),
            ];
            // allow loop to continue
        }
    }

    $stats = [
        'profile_results_count' => DB::table('profile_results')->count(),
        'simulated_trades_count'=> DB::table('simulated_trades')->count(),
        'now' => now()->toDateTimeString(),
    ];

    return redirect('/_profiles_tools')
        ->with('runner_result', [
            'params'  => compact('days','limit','profile'),
            'outputs' => $outputs,
            'stats'   => $stats,
        ]);
})->name('profiles.tools.run.alt');

Route::get('/profiles-tools', fn() => redirect('/_profiles_tools'))->name('profiles.tools.redirect1');
Route::get('/tools/profiles', fn() => redirect('/_profiles_tools'))->name('profiles.tools.redirect2');

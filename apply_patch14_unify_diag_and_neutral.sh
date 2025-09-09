#!/bin/bash
set -e
# Ensure we run from project root (artisan must exist)
if [ ! -f artisan ]; then
  echo "Please run from your Laravel project root (artisan missing)."
  exit 1
fi

# 1) Write/overwrite routes/web.profiles_diag.php
mkdir -p routes
cat > routes/web.profiles_diag.php <<'PHP'
<?php

use Illuminate\Support\Facades\Route;

// Unified diagnostics without ProfileService dependency
Route::get('/profiles/diag', function () {
    $days = (int)request('days', 10);
    $only = request('profile');

    $out = [];

    $out['strategy_profiles_total']   = \DB::table('strategy_profiles')->count();
    $out['strategy_profiles_enabled'] = \DB::table('strategy_profiles')->where('enabled', true)->count();

    $simExists = \DB::getSchemaBuilder()->hasTable('simulated_trades');
    $out['has_simulated_trades_table'] = $simExists;
    if ($simExists) {
        $out['simulated_trades_sample'] = \DB::table('simulated_trades')->orderByDesc(\DB::raw('1'))->limit(3)->get();
    }

    $profile = $only
        ? \App\Models\StrategyProfile::where('enabled',true)->where('id',$only)->first()
        : \App\Models\StrategyProfile::where('enabled',true)->orderBy('id')->first();

    if ($profile) {
        $start  = \Carbon\Carbon::now('Europe/Copenhagen')->subDays($days)->startOfDay();
        $trades = \App\Support\BacktestShim::run($start, $days, $profile->settings ?? [], true);
        $out['shim_trades_count'] = is_array($trades) ? count($trades) : 0;
        $out['shim_trades_head']  = array_slice($trades, 0, 5);
    } else {
        $out['shim_trades_count'] = 0;
        $out['note'] = 'No enabled strategy_profiles found';
    }

    $prExists = \DB::getSchemaBuilder()->hasTable('profile_results');
    $out['has_profile_results_table'] = $prExists;
    if ($prExists) {
        $out['profile_results_count'] = \DB::table('profile_results')->count();
        $out['profile_results_tail']  = \DB::table('profile_results')->orderByDesc('id')->limit(5)->get();
    }

    return response()->json($out);
})->name('profiles.diag');
PHP

# 2) Add neutral /x-diag route in routes/web.php if missing
if ! grep -q "name('x.diag')" routes/web.php; then
  echo "" >> routes/web.php
  cat >> routes/web.php <<'PHP'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/x-diag', function () {
    return response()->json([
        'ok' => true,
        'ts' => now()->toDateTimeString(),
        'routes' => [
            'profiles_diag' => route('profiles.diag', ['days'=>10], false),
        ]
    ]);
})->name('x.diag');
PHP

fi

# 3) Ensure requires exist at bottom of routes/web.php
append_req() {
  local line="$1"
  grep -qF "$line" routes/web.php || echo "$line" >> routes/web.php
}
append_req "require base_path('routes/web.profiles_diag.php');"
append_req "require base_path('routes/web.profiles_run.php');"
append_req "require base_path('routes/web.signals_pretty.php');"

# 4) Clear caches & show routes
php artisan optimize:clear || true
php artisan route:list | grep -E "profiles\.diag|x\.diag|signals\.pretty" || true

echo "Done. Try: /x-diag and /profiles/diag?days=10"

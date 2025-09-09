#!/bin/bash
set -e
WEB="routes/web.php"
BK="routes/web.php.bak_patch15_$(date +%s)"
cp "$WEB" "$BK"
echo ">> Backup taken: $BK"

# Append alias + direct routes if not already present
if ! grep -q "PATCH15: alias routes" "$WEB"; then
  printf "\n%s\n" '// === PATCH15: alias routes for profiles diag (avoid nginx /profiles/ conflicts) ===

Route::get('\''/profiles-diag'\'', function () {
    return redirect()->to('\''/profiles/diag'\'' . (request()->getQueryString() ? '\''?'\'' . request()->getQueryString() : '\'''\''));
})->name('\''profiles.diag.alias1'\'');

Route::get('\''/diag-profiles'\'', function () {
    return redirect()->to('\''/profiles/diag'\'' . (request()->getQueryString() ? '\''?'\'' . request()->getQueryString() : '\'''\''));
})->name('\''profiles.diag.alias2'\'');

// Optional: direct endpoint that bypasses /profiles/* completely
Route::get('\''/_profiles_diag'\'', function () {
    // same logic as /profiles/diag but inline to bypass any external rewrite for /profiles/*
    $days = (int)request('\''days'\'', 10);
    $only = request('\''profile'\'');

    $out = [];
    $out['\''strategy_profiles_total'\'']   = \DB::table('\''strategy_profiles'\'')->count();
    $out['\''strategy_profiles_enabled'\''] = \DB::table('\''strategy_profiles'\'')->where('\''enabled'\'', true)->count();

    $simExists = \DB::getSchemaBuilder()->hasTable('\''simulated_trades'\'');
    $out['\''has_simulated_trades_table'\''] = $simExists;
    if ($simExists) {
        $out['\''simulated_trades_sample'\''] = \DB::table('\''simulated_trades'\'')->orderByDesc(\DB::raw('\''1'\''))->limit(3)->get();
    }

    $profile = $only
        ? \App\Models\StrategyProfile::where('\''enabled'\'',true)->where('\''id'\'',$only)->first()
        : \App\Models\StrategyProfile::where('\''enabled'\'',true)->orderBy('\''id'\'')->first();

    if ($profile) {
        $start  = \Carbon\Carbon::now('\''Europe/Copenhagen'\'')->subDays($days)->startOfDay();
        $trades = \App\Support\BacktestShim::run($start, $days, $profile->settings ?? [], true);
        $out['\''shim_trades_count'\''] = is_array($trades) ? count($trades) : 0;
        $out['\''shim_trades_head'\'']  = array_slice($trades, 0, 5);
    } else {
        $out['\''shim_trades_count'\''] = 0;
        $out['\''note'\''] = '\''No enabled strategy_profiles found'\'';
    }

    $prExists = \DB::getSchemaBuilder()->hasTable('\''profile_results'\'');
    $out['\''has_profile_results_table'\''] = $prExists;
    if ($prExists) {
        $out['\''profile_results_count'\''] = \DB::table('\''profile_results'\'')->count();
        $out['\''profile_results_tail'\'']  = \DB::table('\''profile_results'\'')->orderByDesc('\''id'\'')->limit(5)->get();
    }

    return response()->json($out);
})->name('\''profiles.diag.direct'\'');
// === /PATCH15 ===
' >> "$WEB"
else
  echo ">> PATCH15 already present"
fi

php artisan optimize:clear || true
php artisan route:list | grep -E "profiles\\.diag|profiles-diag|diag-profiles|_profiles_diag|x\\.diag" || true
echo ">> Try these URLs:"
echo "   /profiles-diag?days=10"
echo "   /diag-profiles?days=10"
echo "   /_profiles_diag?days=10   (direct, bypasses /profiles/*)"
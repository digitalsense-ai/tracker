#!/bin/bash
set -e
WEB="routes/web.php"
if ! grep -q "PATCH13I: multi diagnostics routes" "$WEB"; then
  echo ">> Appending PATCH13I multi diagnostics routes to $WEB"
  printf "\n%s\n" '// === PATCH13I: multi diagnostics routes (no extra use) ===

Route::get('\''/diag'\'', function(){ return response()->json(['\''ok'\''=>true,'\''ts'\''=>now()->toDateTimeString(),'\''hint'\'':'\''/diag-profiles, /profiles-diag, /profiles/diag'\'']); })->name('\''diag.root'\'');

Route::get('\''/profiles/diag'\'', function () {
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
})->name('\''profiles.diag'\'');

Route::get('\''/diag-profiles'\'', fn() => redirect('\''/profiles-diag'\''));
Route::get('\''/profiles-diag'\'', function () {
    return redirect()->to('\''/profiles/diag'\''.(request()->getQueryString() ? '\''?'\''.request()->getQueryString() : '\'''\''));
})->name('\''profiles.diag.alt'\'');

Route::get('\''/Profiles/Diag'\'', fn() => redirect()->to('\''/profiles/diag'\''.(request()->getQueryString() ? '\''?'\''.request()->getQueryString() : '\'''\'')));
// === /PATCH13I ===
' >> "$WEB"
else
  echo ">> PATCH13I block already present in $WEB"
fi
php artisan optimize:clear || true
php artisan route:list | grep -E "profiles-diag|profiles\\.diag|diag-profiles|diag\\.root" || true
echo "Try: /diag  /profiles/diag?days=10  /profiles-diag?days=10  /diag-profiles?days=10"
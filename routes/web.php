<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExplainerController;
use App\Http\Controllers\TradeResultController;
use App\Http\Controllers\BacktestController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\ExplainerFlowController;
use App\Http\Controllers\SignalsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ProfilesController;
use App\Http\Controllers\ProfilesRunController;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Support\BacktestShim;
use App\Models\StrategyProfile;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/status', [StatusController::class, 'index'])->name('status');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/kpi', [KpiController::class, 'index'])->name('kpi');

Route::get('/results', [TradeResultController::class, 'index'])->name('results');

Route::get('/backtest', [BacktestController::class, 'index'])->name('backtest');

Route::get('/explainer', [ExplainerController::class, 'index'])->name('explainer');
Route::get('/explainer-flow', [ExplainerFlowController::class, 'show'])->name('explainer.flow');

Route::get('/signals', [SignalsController::class, 'index'])->name('signals');

Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');

Route::get('/profiles',[ProfilesController::class,'index'])->name('profiles.index');
Route::get('/profiles/{id}',[ProfilesController::class,'show'])->name('profiles.show');
Route::get('/profiles/run', [ProfilesRunController::class, 'run'])->name('profiles.run');

Route::get('/signals-pretty', fn() => view('signals.pretty'))->name('signals.pretty');

Route::get('/profiles/diag', function () {
    $days = (int)request('days', 10);
    $only = request('profile');

    $out = [];

    $out['strategy_profiles_total'] = DB::table('strategy_profiles')->count();
    $out['strategy_profiles_enabled'] = DB::table('strategy_profiles')->where('enabled', true)->count();

    $simExists = DB::getSchemaBuilder()->hasTable('simulated_trades');
    $out['has_simulated_trades_table'] = $simExists;
    if ($simExists) {
        $out['simulated_trades_sample'] = DB::table('simulated_trades')->orderByDesc(DB::raw('1'))->limit(3)->get();
    }

    $profile = $only
        ? StrategyProfile::where('enabled',true)->where('id',$only)->first()
        : StrategyProfile::where('enabled',true)->orderBy('id')->first();

    if ($profile) {
        $start = Carbon::now('Europe/Copenhagen')->subDays($days)->startOfDay();
        $trades = BacktestShim::run($start, $days, $profile->settings ?? [], true);
        $out['shim_trades_count'] = is_array($trades) ? count($trades) : 0;
        $out['shim_trades_head'] = array_slice($trades, 0, 5);
    } else {
        $out['shim_trades_count'] = 0;
        $out['shim_trades_head'] = [];
        $out['note'] = 'No enabled strategy_profiles found';
    }

    $prExists = DB::getSchemaBuilder()->hasTable('profile_results');
    $out['has_profile_results_table'] = $prExists;
    if ($prExists) {
        $out['profile_results_count'] = DB::table('profile_results')->count();
        $out['profile_results_tail'] = DB::table('profile_results')->orderByDesc('id')->limit(5)->get();
    }

    return response()->json($out);
});
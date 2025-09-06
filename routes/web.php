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

require base_path('routes/web.signals_pretty.php');
require base_path('routes/web.profiles_run.php');
require base_path('routes/web.profiles_diag.php');
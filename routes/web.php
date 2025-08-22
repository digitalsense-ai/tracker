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

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/status', [StatusController::class, 'index'])->name('status');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/kpi', [KpiController::class, 'index'])->name('kpi');

Route::get('/results', [TradeResultController::class, 'index'])->name('results');

Route::get('/backtest', [BacktestController::class, 'index'])->name('backtest');

Route::get('/explainer', [ExplainerController::class, 'index'])->name('explainer');
Route::get('/explainer-flow', [ExplainerFlowController::class, 'index'])->name('explainer.flow');

Route::get('/signals', [SignalsController::class, 'index'])->name('signals');

Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
Route::post('/settings', [SettingsController::class, 'store'])->name('settings.store');
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExplainerController;
use App\Http\Controllers\TradeResultController;
use App\Http\Controllers\BacktestController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\ExplainerFlowController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/explainer', [ExplainerController::class, 'index'])->name('explainer');

Route::get('/results', [TradeResultController::class, 'index'])->name('results');

Route::get('/backtest', [BacktestController::class, 'index'])->name('backtest');

Route::get('/status', [StatusController::class, 'index'])->name('status');
Route::get('/kpi', [KpiController::class, 'index'])->name('kpi');
Route::get('/explainer-flow', [ExplainerFlowController::class, 'index'])->name('explainer.flow');
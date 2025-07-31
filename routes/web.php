<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExplainerController;
use App\Http\Controllers\TradeResultController;

Route::get('/dashboard', [DashboardController::class, 'index']);
Route::post('/update-forecast-config', [DashboardController::class, 'updateForecastConfig']);
Route::get('/explainer', [ExplainerController::class, 'index']);
Route::get('/results', [TradeResultController::class, 'index']);

use App\Http\Controllers\BacktestController;
Route::get('/backtest', [BacktestController::class, 'index']);

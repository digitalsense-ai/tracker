<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExplainerController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\BacktestController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/explainer', [ExplainerController::class, 'index'])->name('explainer');
Route::get('/results', [ResultController::class, 'index'])->name('results');
Route::get('/backtest', [BacktestController::class, 'index'])->name('backtest');

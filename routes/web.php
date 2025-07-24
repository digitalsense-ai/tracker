<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/dashboard', [DashboardController::class, 'index']);
Route::post('/update-forecast-config', [DashboardController::class, 'updateForecastConfig']);
Route::get('/explainer', [App\Http\Controllers\ExplainerController::class, 'index']);

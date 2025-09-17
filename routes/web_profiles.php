<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileLeaderboardController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/profiles/leaderboard', [ProfileLeaderboardController::class, 'index'])
        ->name('profiles.leaderboard');
});

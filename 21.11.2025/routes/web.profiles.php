<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileLeaderboardController;

Route::get('/profiles', function(){
    return view('profiles.index');
})->name('profiles.index');

// Route::get('/profiles/leaderboard', function(){
//     return view('profiles.leaderboard');
// })->name('profiles.leaderboard');

//Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/profiles/leaderboard', [ProfileLeaderboardController::class, 'index'])
        ->name('profiles.leaderboard');
//});
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Services\ProfileService;

// Run backtest for profiles
Route::get('/profiles/run', function (Request $request) {
    $days = (int) $request->query('days', 5);
    $profile = (int) $request->query('profile', 1);

    $svc = app(ProfileService::class);
    $result = $svc->backtestProfile($profile, $days);

    return response()->json([
        'ok'      => true,
        'profile' => $profile,
        'days'    => $days,
        'result'  => $result,
    ]);
})->name('profiles.run');

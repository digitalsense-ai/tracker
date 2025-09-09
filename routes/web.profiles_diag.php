<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Services\ProfileService;

// Profiles diagnostics (JSON)
Route::get('/profiles/diag', function (Request $request) {
    $days = (int) $request->query('days', 5);
    $limit = (int) $request->query('limit', 50);

    $svc = app(ProfileService::class);
    $data = $svc->diagnostics($days, $limit);

    return response()->json([
        'ok'   => true,
        'days' => $days,
        'limit'=> $limit,
        'data' => $data,
    ]);
})->name('profiles.diag');

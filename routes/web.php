<?php
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

/**
 * RESCUED web.php (Patch16C)
 * - Original file moved to routes/web.php.broken_<timestamp>
 * - This minimal version loads essential routes and separates risky blocks.
 */

use Illuminate\Support\Facades\Route;

// Basic health routes
Route::get('/ping', fn() => response('pong: web.php OK', 200))->name('ping');
Route::get('/routes-dump', function () {
    $out = [];
    foreach (Route::getRoutes() as $r) {
        $out[] = ['uri' => $r->uri(), 'name' => $r->getName(), 'methods' => $r->methods()];
    }
    return response()->json($out);
})->name('routes.dump');

// Load your split route files when present (no fatal if missing)
foreach ([
    'routes/web.signals_pretty.php',
    'routes/web.profiles_run.php',
    'routes/web.profiles_diag.php',
    'routes/web.profiles_tools.php',
    'routes/patch16b_tools_alias.php',
] as $rel) {
    $path = base_path($rel);
    if (file_exists($path)) { require $path; }
}

// You can add any additional requires here safely.
// DO NOT put raw HTML or close PHP tags in this file.
require __DIR__.'/web.profiles.php';
require __DIR__.'/web.profiles_run.php';
require __DIR__.'/web.profiles_diag.php';
require __DIR__.'/web.signals_pretty.php';

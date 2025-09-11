<?php
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

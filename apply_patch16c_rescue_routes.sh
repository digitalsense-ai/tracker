#!/bin/bash
set -e

echo "== Patch16C: Rescue routes/web.php =="

# Locate project root (artisan)
CANDIDATES=("$PWD" "$(cd "$(dirname "$0")" && pwd)" "$(cd "$(dirname "$0")" && pwd)/.." "/var/www/tracker")
PROJECT_DIR=""
for c in "${CANDIDATES[@]}"; do
  if [ -f "$c/artisan" ]; then PROJECT_DIR="$c"; break; fi
done
[ -z "$PROJECT_DIR" ] && echo "artisan not found" && exit 1
echo "Project: $PROJECT_DIR"

WEB="$PROJECT_DIR/routes/web.php"
TS=$(date +%s)
BROKEN="$PROJECT_DIR/routes/web.php.broken_$TS"

# 1) Move current web.php aside
cp "$WEB" "$BROKEN"
echo "Backup: $BROKEN"

# 2) Write a fresh, safe web.php
cat > "$WEB" <<'PHP'
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
PHP


# 3) Install alias file (separate, safe PHP file)
cat > "$PROJECT_DIR/routes/patch16b_tools_alias.php" <<'PHP'
<?php

use Illuminate\Support\Facades\Route;

// === patch16B alias (safe separate file) ===
Route::get('/_profiles_tools', function () {
    return view('profiles.run', [
        'defaults' => [
            'days'  => request('days', 10),
            'limit' => request('limit', 50),
            'profile'=> request('profile', ''),
        ],
    ]);
})->name('profiles.tools.alt');

Route::post('/_profiles_tools/run', function () {
    $r = request();
    $days   = (int) $r->input('days', 10);
    $limit  = (int) $r->input('limit', 50);
    $profile = $r->input('profile');

    $outputs = [];

    $candidates = [
        ['cmd' => 'profiles:backtest',       'opts' => ['--days' => $days, '--limit' => $limit, '-vv' => true]],
        ['cmd' => 'profiles:baseline',       'opts' => ['--days' => $days, '--limit' => $limit, '-vv' => true]],
        ['cmd' => 'profiles:backtest-hotfix','opts' => ['--days' => $days, '--limit' => $limit, '-vv' => true]],
        ['cmd' => 'profiles:diag',           'opts' => []],
    ];

    if ($profile !== null && $profile !== '') {
        foreach ($candidates as &$c) {
            $c['opts']['--profile'] = $profile;
        }
    }

    foreach ($candidates as $c) {
        try {
            $buffer = new Symfony\Component\Console\Output\BufferedOutput;
            \Illuminate\Support\Facades\Artisan::call($c['cmd'], $c['opts'], $buffer);
            $out = $buffer->fetch();
            $outputs[] = [
                'command' => $c['cmd'],
                'options' => $c['opts'],
                'output'  => $out,
            ];
            if (trim($out) !== '') { break; }
        } catch (Throwable $e) {
            $outputs.append([
                'command' => $c['cmd'],
                'options' => $c['opts'],
                'error'   => $e->getMessage(),
            ])
            continue;
        }
    }

    $stats = [
        'profile_results_count' => \Illuminate\Support\Facades\DB::table('profile_results')->count(),
        'simulated_trades_count'=> \Illuminate\Support\Facades\DB::table('simulated_trades')->count(),
        'now' => now()->toDateTimeString(),
    ];

    return redirect('/_profiles_tools')
        ->with('runner_result', [
            'params'  => compact('days','limit','profile'),
            'outputs' => $outputs,
            'stats'   => $stats,
        ]);
})->name('profiles.tools.run.alt');

Route::get('/profiles-tools', fn() => redirect('/_profiles_tools'))->name('profiles.tools.redirect1');
Route::get('/tools/profiles', fn() => redirect('/_profiles_tools'))->name('profiles.tools.redirect2');
PHP


# 4) Clear caches and lint
(cd "$PROJECT_DIR" && php -l routes/web.php)
(cd "$PROJECT_DIR" && php artisan optimize:clear || true)
(cd "$PROJECT_DIR" && php artisan route:list | grep -E "routes\\.dump|_profiles_tools|profiles\\.tools|profiles\\.diag" || true)

echo "✅ Rescue complete. Try:"
echo "  - /routes-dump"
echo "  - /_profiles_tools"
echo "  - /profiles/diag?days=10"

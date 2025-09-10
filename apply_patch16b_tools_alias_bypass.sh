#!/bin/bash
set -e

echo "== Applying PATCH16B Tools alias/bypass =="

SCRIPT_DIR="$(cd "$(dirname "{BASH_SOURCE[0]}")" && pwd)"
CANDIDATES=("$PWD" "$SCRIPT_DIR" "$SCRIPT_DIR/.." "/var/www/tracker")
PROJECT_DIR=""
for c in "${CANDIDATES[@]}"; do
  if [ -f "$c/artisan" ]; then PROJECT_DIR="$c"; break; fi
done
if [ -z "$PROJECT_DIR" ]; then
  echo "❌ Could not find Laravel project root (artisan not found)."
  exit 1
fi
echo "Project: $PROJECT_DIR"

WEB="$PROJECT_DIR/routes/web.php"
BK="$WEB.bak_patch16b_$(date +%s)"
cp "$WEB" "$BK"
echo "Backup: $BK"

if ! grep -q "PATCH16B: Tools alias/bypass" "$WEB"; then
  echo ">> Appending alias routes"
  printf "\n%s\n" "// === PATCH16B: Tools alias/bypass (avoid webserver /profiles/* rules) ===

Route::get('/_profiles_tools', function () {
    return view('profiles.run', [
        'defaults' => [
            'days'    => request('days', 10),
            'limit'   => request('limit', 50),
            'profile' => request('profile', ''),
        ],
    ]);
})->name('profiles.tools.alt');

Route::post('/_profiles_tools/run', function () {
    \$r = request();
    \$days    = (int) \$r->input('days', 10);
    \$limit   = (int) \$r->input('limit', 50);
    \$profile = \$r->input('profile');

    \$outputs = [];

    \$candidates = [
        ['cmd' => 'profiles:backtest',        'opts' => ['--days' => \$days, '--limit' => \$limit, '-vv' => true]],
        ['cmd' => 'profiles:baseline',        'opts' => ['--days' => \$days, '--limit' => \$limit, '-vv' => true]],
        ['cmd' => 'profiles:backtest-hotfix', 'opts' => ['--days' => \$days, '--limit' => \$limit, '-vv' => true]],
        ['cmd' => 'profiles:diag',            'opts' => []],
    ];

    if (\$profile !== null && \$profile !== '') {
        foreach (\$candidates as &\$c) {
            \$c['opts']['--profile'] = \$profile;
        }
    }

    foreach (\$candidates as \$c) {
        try {
            \$buffer = new Symfony\Component\Console\Output\BufferedOutput;
            \Illuminate\Support\Facades\Artisan::call(\$c['cmd'], \$c['opts'], \$buffer);
            \$out = \$buffer->fetch();
            \$outputs[] = [
                'command' => \$c['cmd'],
                'options' => \$c['opts'],
                'output'  => \$out,
            ];
            if (trim(\$out) !== '') {
                break;
            }
        } catch (Throwable \$e) {
            \$outputs[] = [
                'command' => \$c['cmd'],
                'options' => \$c['opts'],
                'error'   => \$e->getMessage(),
            ];
            continue;
        }
    }

    \$stats = [
        'profile_results_count'  => \Illuminate\Support\Facades\DB::table('profile_results')->count(),
        'simulated_trades_count' => \Illuminate\Support\Facades\DB::table('simulated_trades')->count(),
        'now'                    => now()->toDateTimeString(),
    ];

    return redirect('/_profiles_tools')
        ->with('runner_result', [
            'params'  => compact('days','limit','profile'),
            'outputs' => \$outputs,
            'stats'   => \$stats,
        ]);
})->name('profiles.tools.run.alt');

Route::get('/profiles-tools', fn() => redirect('/_profiles_tools'))->name('profiles.tools.redirect1');
Route::get('/tools/profiles', fn() => redirect('/_profiles_tools'))->name('profiles.tools.redirect2');

// === /PATCH16B ===
" >> "$WEB"
else
  echo ">> PATCH16B already present"
fi


(cd "$PROJECT_DIR" && php artisan optimize:clear || true)
(cd "$PROJECT_DIR" && php artisan route:list | grep -E "profiles\.tools|_profiles_tools" || true)

echo "✅ Done. Try: /_profiles_tools"

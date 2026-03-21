<?php
use App\Http\Controllers\TestSampleController;

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
use App\Http\Controllers\Profiles\LeaderboardController;

use App\Http\Controllers\LiveController;
use App\Http\Controllers\ModelsController;
use App\Http\Controllers\PlanKanbanController;
use App\Http\Controllers\PlanKanbanControllerV2;
use App\Http\Controllers\ModelChatController;
use App\Http\Controllers\ModelLogController;
use App\Http\Controllers\ModelPromptController;
use App\Http\Controllers\TradeReviewController;
use App\Http\Controllers\FeedbackSummaryController;

//use App\Models\AiModel;
Route::get('/testsample/bar', [TestSampleController::class, 'index'])->name('testsample.bar');

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

Route::get('/profiles/leaderboard', [LeaderboardController::class, 'index'])->name('profiles.leaderboard');

Route::get('/', [LiveController::class, 'index'])->name('live.index');
Route::get('/profiles/leaderboard', [ProfilesController::class, 'leaderboard'])->name('profiles.leaderboard');
Route::get('/profiles/{slug}', [ProfilesController::class, 'show'])->name('profiles.show');

Route::get('/models', [ModelsController::class, 'index'])->name('models.index');
Route::get('/models/create', [ModelsController::class, 'create'])->name('models.create');
Route::post('/models', [ModelsController::class, 'store'])->name('models.store');
Route::get('/models/{model}/edit', [ModelsController::class, 'edit'])->name('models.edit');
Route::put('/models/{model}', [ModelsController::class, 'update'])->name('models.update');
Route::get('/models/{slug}', [ModelsController::class, 'show'])->name('models.show');

Route::get('/models/{slug}/kanban', [PlanKanbanController::class, 'index'])->name('models.kanban');
Route::get('/models/{slug}/kanban-v2', [PlanKanbanControllerV2::class, 'index'])->name('models.kanban.v2');
Route::post('/models/{slug}/kanban', [PlanKanbanController::class, 'update'])->name('models.kanban.update');
Route::get('/models/{slug}/export/{trade_id}', [PlanKanbanController::class, 'exportCompletedTrades'])->name('models.trade.export');

Route::get('/models/{slug}/chat', [ModelChatController::class, 'index'])->name('models.chat');
Route::get('/models/{slug}/log', [ModelLogController::class, 'index'])->name('models.log');

Route::get('/models/{slug}/prompt/{prompt}', [ModelPromptController::class, 'index'])->name('models.prompt');
Route::post('/toggle-prompt-status/{id}', [ModelsController::class, 'toggle']);

Route::get('/leaderboard', [\App\Http\Controllers\LeaderboardController::class, 'index'])
   ->name('leaderboard.index');

Route::get('/live', [\App\Http\Controllers\LiveController::class, 'index'])
   ->name('live.index');

Route::get('/saxo/login', function () {
    $state = Str::random(40);

    session(['saxo_oauth_state' => $state]);

    $url = 'https://sim.logonvalidation.net/authorize?' . http_build_query([
        'response_type' => 'code',
        'client_id'     => config('services.saxo.app_key'),
        'redirect_uri'  => route('saxo.callback'),
        'state'         => $state,
    ]);

    return redirect()->away($url);
})->name('saxo.login');

Route::get('/saxo/callback', function (
    Illuminate\Http\Request $request,
    App\Services\SaxoTokenService $tokenService
) {
    $request->validate([
        'code'  => 'required|string',
        'state' => 'required|string',
    ]);

    if ($request->state !== session('saxo_oauth_state')) {
        abort(403, 'Invalid OAuth state');
    }

    // 🔑 Exchange ONCE
    $tokenService->exchangeCode($request->code);

    // Optional: clear state
    session()->forget('saxo_oauth_state');

    return 'Saxo connected successfully 🎉';
})->name('saxo.callback');

Route::get('/trade-reviews', [TradeReviewController::class, 'index'])->name('trade-reviews.index');
Route::get('/trade-reviews/{tradeReview}', [TradeReviewController::class, 'show'])->name('trade-reviews.show');

Route::get('/feedback-summaries', [FeedbackSummaryController::class, 'index'])->name('feedback-summaries.index');
Route::get('/feedback-summaries/{aiModel}', [FeedbackSummaryController::class, 'show'])->name('feedback-summaries.show');

// Route::get('/saxo/login', function() {
//     $state = csrf_token();
//     $url = 'https://sim.logonvalidation.net/authorize?' . http_build_query([
//         'response_type' => 'code',
//         'client_id'     => config('services.saxo.app_key'),
//         'redirect_uri'  => route('saxo.callback'),
//         'state'         => $state,
//     ]);
//     return redirect()->away($url);
// })->name('saxo.login');

// Route::get('/saxo/callback', function(\Illuminate\Http\Request $request, \App\Services\SaxoTokenService $tokenService) {
//     $request->validate([
//         'code'  => 'required|string',
//         'state' => 'required|string',
//     ]);

//     if ($request->state !== csrf_token()) {
//         abort(403, 'Invalid state');
//     }

//     $accessToken = $tokenService->getToken($request->code);

//     return "Saxo token cached! You can now call MarketData->getPrice()";
// })->name('saxo.callback');


// Route::get('/test-ai-model', function () {
//     $m = \App\Models\AiModel::first() ?? new \App\Models\AiModel([
//         'name' => 'DeepSeek V3.1',
//         'slug' => 'deepseek-v31',
//     ]);

//     $m->equity = 100000;
//     $m->cash = 100000;
//     $m->risk_pct = 0.5;
//     $m->check_interval_min = 1;
//     $m->loop_prompt = 'Every call, return JSON with {action, orders[], notes}. Prefer OPEN/CLOSE/ADJUST/HOLD. Orders need ticker, side, entry, stop, target.';
//     $m->save();

//     return 'Saved';
// });

// Route::get('/update-ai-model', function () {
//     $m = AiModel::first();

//     if (! $m) {
//         return 'No AiModel record found.';
//     }

//     $m->start_equity = $m->equity ?? 100000;
//     $m->peak_equity = $m->equity ?? 100000;
//     $m->max_concurrent_trades = 5;
//     $m->allow_same_symbol_reentry = false;
//     $m->cooldown_minutes = 0;
//     $m->per_trade_alloc_pct = 20;
//     $m->max_exposure_pct = 80;
//     $m->max_leverage = 5;
//     $m->max_drawdown_pct = 0;

//     $m->save();

//     return 'AI Model updated successfully!';
// });


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
        $action = $r->getAction();

        $out[] = ['uri' => $r->uri(), 'name' => $r->getName(), 'methods' => $r->methods(), 'controller' => $action['controller'] ?? null,];
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
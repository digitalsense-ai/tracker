<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

use App\Models\AiModel;
use App\Models\ModelLog;
use App\Models\Trade;
use App\Models\EquitySnapshot;
use App\Models\Position;
use App\Models\AiDailyPlan;
use App\Services\AiDecisionParser;
use App\Services\PaperBroker;

use Carbon\Carbon;
class AiTick extends Command
{   
   protected $signature = 'ai:tick {--model_id=}';
   protected $description = 'Runs one tick for all active AI trading models';
   public function handle()
   {
      $now = Carbon::now();
      
      $query = AiModel::where('active', true);
      if ($this->option('model_id')) {
        $query->where('id', $this->option('model_id'));
      }
      $models = $query->get();

       $broker = app(PaperBroker::class);

       foreach ($models as $model) {
           try {
               // ------------------------------
               // 1) Respect check interval
               // ------------------------------           
               $interval = $model->check_interval
                    ?? $model->check_interval_min
                    ?? 1;

                /**
                 * 🔒 ATOMIC CLAIM
                 * Only ONE process can pass this
                 */
                $claimed = AiModel::where('id', $model->id)
                    ->where(function ($q) use ($now, $interval) {
                        $q->whereNull('last_checked_at')
                          ->orWhere('last_checked_at', '<=', $now->copy()->subMinutes($interval));
                    })
                    ->update([
                        'last_checked_at' => $now, // claim immediately
                    ]);

                // Another process already took it → skip safely
                if ($claimed === 0) {
                    continue;
                }
        
               $this->info("Ticking model: {$model->name}");
               // ------------------------------
               // 2) Load state for this model
               // ------------------------------
               $openPositions = Position::where('ai_model_id', $model->id)
                   ->where('status', 'open')
                   ->get();
               $recentTrades = Trade::where('ai_model_id', $model->id)
                   ->orderByDesc('opened_at')
                   ->limit(10)
                   ->get();
               // Compute current exposure (very simple version)
               $equity = (float) ($model->equity ?? 0);
               $grossExposure = 0.0;
               foreach ($openPositions as $p) {
                   $notional = (float) $p->qty * (float) $p->avg_price;
                   $grossExposure += abs($notional);
               }
               $openExposurePct = $equity > 0 ? ($grossExposure / $equity) * 100.0 : 0.0;
               // Risk / guardrail fields (adjust names if your columns differ)
               $riskLimits = [
                   'max_concurrent_positions'   => (int)   ($model->max_concurrent_trades ?? 5),
                   'allow_same_symbol_reentry'  => (bool)  ($model->allow_same_symbol_reentry ?? false),
                   'cooldown_minutes'           => (int)   ($model->cooldown_minutes ?? 0),
                   'per_trade_alloc_pct'        => (float) ($model->per_trade_alloc_pct ?? 20),
                   'max_exposure_pct'           => (float) ($model->max_exposure_pct ?? 80),
                   'max_leverage'               => (float) ($model->max_leverage ?? 5),
                   'max_drawdown_pct'           => (float) ($model->max_drawdown_pct ?? 0),
               ];
               // Normalize open positions for the AI
               $openPositionsState = $openPositions->map(function (Position $p) {
                   return [
                       'symbol'        => $p->ticker, // adjust if your column is different
                       'side'          => strtoupper($p->side),
                       'qty'           => (float) $p->qty,
                       'avg_price'     => (float) $p->avg_price,
                       'stop_price'    => $p->stop_price !== null ? (float) $p->stop_price : null,
                       'target_price'  => $p->target_price !== null ? (float) $p->target_price : null,
                       'unrealized_pnl'=> $p->unrealized_pnl !== null ? (float) $p->unrealized_pnl : null,
                       'opened_at'     => optional($p->opened_at)->toIso8601String(),
                   ];
               })->values()->all();
               // Normalize recent trades
               $recentTradesState = $recentTrades->map(function (Trade $t) {
                   return [
                       'symbol'      => $t->ticker ?? $t->symbol ?? null,
                       'side'        => strtoupper($t->side),
                       'qty'         => (float) $t->qty,
                       'entry_price' => (float) $t->entry_price,
                       'exit_price'  => $t->exit_price !== null ? (float) $t->exit_price : null,
                       'net_pnl'     => $t->net_pnl !== null ? (float) $t->net_pnl : null,
                       'opened_at'   => optional($t->opened_at)->toIso8601String(),
                       'closed_at'   => optional($t->closed_at)->toIso8601String(),
                   ];
               })->values()->all();              

                // Load today\'s daily plan if it exists              
                $dailyPlanModel = AiDailyPlan::where('ai_model_id', $model->id)                  
                    ->orderByDesc('trade_date')
                   ->first();
                $fullPlan = $dailyPlanModel ? ($dailyPlanModel->plan_json ?? []) : [];
                $dailyPlan = [];
                if (is_array($fullPlan)) {
                   foreach ($fullPlan as $s) {
                       if (!is_array($s)) {
                           continue;
                       }
                       // Treat these as approved:
                       // - explicit approved = true
                       // - status === 'approved'
                       // - keep = true (checkbox semantics)
                       $approved =
                           (!empty($s['approved']) && $s['approved']) ||
                           (($s['status'] ?? null) === 'approved') ||
                           (!empty($s['keep']));
                       if ($approved) {
                           $dailyPlan[] = $s;
                       }
                   }
                }  

                // Allowed symbols = open positions OR approved daily plan
                // $allowedSymbols = collect($openPositionsState)->pluck('symbol')
                //     ->merge(collect($dailyPlan)->pluck('symbol'))
                //     ->map(fn ($s) => strtoupper($s))
                //     ->unique()
                //     ->values()
                //     ->all();    

                $planSymbols = collect($dailyPlan)->map(function ($s) {
                    return strtoupper($s['symbol'] ?? $s['ticker'] ?? '');
                })->filter();

                $allowedSymbols = collect($openPositionsState)->pluck('symbol')
                                    ->merge($planSymbols)
                                    ->map(fn ($s) => strtoupper($s))
                                    ->filter()
                                    ->unique()
                                    ->values()
                                    ->all();         

                // Attach current prices for any relevant symbols (open positions + plan)
                $prices = [];
                $marketData = app(\App\Services\MarketData::class);                

                foreach ($openPositions as $p) {
                    $ticker = $p->ticker;

                    // Cache key per symbol
                    $cacheKey = "price_prev_{$ticker}";
                    // Get previous price (null if not exists)
                    $prevPrice = Cache::get($cacheKey);

                    if ($ticker && !isset($prices[$ticker])) {
                        $currentPrice = (float) $marketData->getPrice($ticker);

                        $prices[$ticker] = [
                            'last' => $currentPrice,
                            'prev' => $prevPrice
                        ];
                        
                        // Update cache for next iteration
                        Cache::put($cacheKey, $currentPrice, now()->addMinutes($interval + 5));
                    }
                }

                foreach ($dailyPlan as $s) {
                    if (!is_array($s)) {
                        continue;
                    }
                    $ticker = $s['symbol'] ?? null;
                    if ($ticker) {
                        $ticker = strtoupper($ticker);

                        // Cache key per symbol
                        $cacheKey = "price_prev_{$ticker}";
                        // Get previous price (null if not exists)
                        $prevPrice = Cache::get($cacheKey);

                        if (!isset($prices[$ticker])) {
                            $currentPrice = (float) $marketData->getPrice($ticker);

                            $prices[$ticker] = [
                                'last' => $currentPrice,
                                'prev' => $prevPrice
                            ];

                            // Update cache for next iteration
                            Cache::put($cacheKey, $currentPrice, now()->addMinutes($interval + 5));
                        }
                    }
                }

                ModelLog::create([
                    'ai_model_id' => $model->id,
                    'action'      => 'PLAN_DEBUG',
                    'payload'     => [
                        'full_plan_count'  => is_array($fullPlan) ? count($fullPlan) : null,
                        'daily_plan_count' => count($dailyPlan),
                        'sample_full_plan' => is_array($fullPlan) ? array_slice($fullPlan, 0, 3) : $fullPlan,
                        'sample_daily'     => array_slice($dailyPlan, 0, 3),
                        'open_positions_count' => count($openPositionsState),
                        'prices_keys' => array_keys($prices),
                        'prices' => $prices,
                    ],
                ]);
                
               //Build state object for the AI
                /*
               $state = [
                   'model' => [
                       'name'           => $model->name,
                       'equity'         => $equity,
                       'start_equity'   => (float) ($model->start_equity ?? $equity),
                       'goal'           => $model->goal_label ?? null,
                       'risk_per_trade' => (float) ($model->risk_pct ?? 0),
                   ],
                   'time'              => now()->toIso8601String(),
                   'open_exposure_pct' => $openExposurePct,
                   'risk_limits'       => $riskLimits,
                   'open_positions'    => $openPositionsState,
                   'recent_trades'     => $recentTradesState,
                   'prices'           => $prices,
                   'daily_plan'       => $dailyPlan,
                   'full_plan'        => $fullPlan,
                   'allowed_symbols' => $allowedSymbols,
               ];
               */

               //SESSION
                // Compute minutes since market open (example: 09:30 ET)                        
                $nowNy = Carbon::now('America/New_York');
                $marketOpen = $nowNy->copy()->setTime(9, 30, 0);
                $minutesSinceOpen = $nowNy->greaterThanOrEqualTo($marketOpen)
                    ? $marketOpen->diffInMinutes($nowNy)
                    : 0;

                if ($nowNy->lt($marketOpen)) {
                    $phase = 'premarket';
                } elseif ($nowNy->hour < 16) {
                    $phase = 'intraday';
                } else {
                    $phase = 'close';
                }
                //end SESSION

                //ACCOUNT
                // 1️ Compute used exposure %
                $grossExposure = 0.0;
                foreach ($openPositions as $p) {
                    $notional = (float) $p->qty * (float) $p->avg_price;
                    $grossExposure += abs($notional);
                }
                $usedExposurePct = $equity > 0 ? ($grossExposure / $equity) * 100 : 0;

                // 2️ Compute day PnL (unrealized + realized today)
                $dayPnl = 0.0;
                // unrealized PnL from open positions
                foreach ($openPositions as $p) {
                    $lastPrice = $prices[$p->ticker]['last'] ?? $p->avg_price;
                    $dayPnl += ($lastPrice - $p->avg_price) * $p->qty * ($p->side === 'LONG' ? 1 : -1);
                }
                // realized PnL from trades closed today
                foreach ($recentTrades as $t) {
                    $tradeDate = Carbon::parse($t->opened_at)->toDateString();
                    if ($tradeDate === $now->toDateString()) {
                        $dayPnl += $t->net_pnl ?? 0;
                    }
                }

                // 3️ Compute drawdown % from start equity
                $startEquity = (float) ($model->start_equity ?? $equity);
                $drawdownPct = $startEquity > 0 ? (($startEquity - $equity) / $startEquity) * 100 : 0;                
                //end ACCOUNT

                $baseTradeBudget = $equity > 0
                                    ? round($equity * ((float) ($model->per_trade_alloc_pct ?? 0) / 100), 2)
                                    : 0.0;

                //WATCHLIST
                $watchlist = [];

                foreach ($allowedSymbols as $ticker) {
                    $ticker = strtoupper($ticker);
                    $priceData = $prices[$ticker] ?? ['last' => null, 'prev' => null];
                    $last = $priceData['last'] ?? 0;
                    $prevClose = $marketData->getPreviousClose($ticker); // implement this in MarketData service
                    $vwap = $marketData->getIntradayVWAP($ticker);       // implement VWAP calculation
                    $avgVolume = $marketData->getAverageVolume($ticker); // e.g., 20-day avg
                    $currentVolume = $marketData->getCurrentVolume($ticker);
                    
                    // Find planned entry for this ticker if exists                   
                    $planItem = collect($dailyPlan)->first(function ($item) use ($ticker) {
                        return strtoupper($item['symbol'] ?? $item['ticker'] ?? '') === $ticker;
                    });
                    $entryLow = $planItem['entry_zone_low'] ?? $last;
                    $entryHigh = $planItem['entry_zone_high'] ?? $last;

                    $entryReference = $entryLow ?: ($entryHigh ?: $last);
                    $maxQty = ($entryReference > 0 && $baseTradeBudget > 0)
                        ? (int) floor($baseTradeBudget / $entryReference)
                        : 0;

                    // Computed metrics
                    $dayChangePct = $prevClose ? (($last - $prevClose) / $prevClose) * 100 : 0;
                    $distanceToEntryPct = $last ? min(abs(($last - $entryLow) / $entryLow), abs(($last - $entryHigh) / $entryHigh)) * 100 : 0;
                    $distanceToVWAPPct = $vwap ? (($last - $vwap) / $vwap) * 100 : 0;
                    $relativeVolume = $avgVolume ? $currentVolume / $avgVolume : 1;
                    $intradayRangePct = $prevClose ? ($marketData->getIntradayHigh($ticker) - $marketData->getIntradayLow($ticker)) / $prevClose * 100 : 0;

                    // Simple regime hint
                    if ($dayChangePct > 1 && $distanceToEntryPct < 1) {
                        $regimeHint = 'breakout';
                    } elseif ($dayChangePct < -1) {
                        $regimeHint = 'pullback';
                    } else {
                        $regimeHint = 'neutral';
                    }

                    $watchlist[] = [
                        'ticker' => $ticker,
                        'last' => $last,
                        'prev_loop_price' => $priceData['prev'] ?? $last,
                        'change_from_prev_loop_pct' => $priceData['prev'] ? (($last - $priceData['prev']) / $priceData['prev']) * 100 : 0,
                        'day_change_pct' => round($dayChangePct, 2),
                        'distance_to_entry_pct' => round($distanceToEntryPct, 2),
                        'distance_to_vwap_pct' => round($distanceToVWAPPct, 2),
                        'relative_volume' => round($relativeVolume, 2),
                        'intraday_range_pct' => round($intradayRangePct, 2),
                        'regime_hint' => $regimeHint,

                        'entry_reference' => round((float) $entryReference, 4),
                        'base_trade_budget' => $baseTradeBudget,
                        'max_qty' => $maxQty,
                        'allowed_size_multipliers' => [0.25, 0.5, 1.0],                       
                    ];
                }
                //end WATCHLIST

                //MARKET CONTENT
                // compute market context dynamically
                $marketContext = [];

                // 1️ Trend: based on price momentum of tracked symbols
                $priceChanges = array_map(fn($p) => $p['last'] - ($p['prev'] ?? $p['last']), $prices);
                $avgChange = count($priceChanges) ? array_sum($priceChanges) / count($priceChanges) : 0;

                if ($avgChange > 0.2) { // adjust threshold as needed
                    $marketContext['trend'] = 'bullish';
                } elseif ($avgChange < -0.2) {
                    $marketContext['trend'] = 'bearish';
                } else {
                    $marketContext['trend'] = 'neutral';
                }

                // 2️ Volatility: use intraday price range
                $intradayRanges = array_map(fn($p) => $p['last'] - ($p['prev'] ?? $p['last']), $prices);
                $avgRange = count($intradayRanges) ? array_sum(array_map('abs', $intradayRanges)) / count($intradayRanges) : 0;

                if ($avgRange < 0.5) {
                    $marketContext['volatility'] = 'low';
                } elseif ($avgRange < 2) {
                    $marketContext['volatility'] = 'normal';
                } else {
                    $marketContext['volatility'] = 'high';
                }

                // 3️ Breadth: number of positions advancing vs declining
                $up = 0; $down = 0;
                foreach ($prices as $p) {
                    if (($p['last'] ?? 0) > ($p['prev'] ?? 0)) $up++;
                    elseif (($p['last'] ?? 0) < ($p['prev'] ?? 0)) $down++;
                }
                $total = $up + $down;
                if ($total > 0) {
                    $upPct = $up / $total;
                    if ($upPct > 0.6) $marketContext['breadth'] = 'strong';
                    elseif ($upPct < 0.4) $marketContext['breadth'] = 'weak';
                    else $marketContext['breadth'] = 'mixed';
                } else {
                    $marketContext['breadth'] = 'mixed';
                }

                // 4️ Risk-on: simple rule – positive market trend + low volatility
                $marketContext['risk_on'] = ($marketContext['trend'] === 'bullish' && $marketContext['volatility'] !== 'high');
                //end MARKET CONTENT

                $state = [
                    'time' => now()->toIso8601String(),

                    'session' => [
                        'market' => 'US',
                        'phase' => $phase,
                        'minutes_since_open' => $minutesSinceOpen,
                    ],

                    'model' => [
                        'name'                      => $model->name,
                        'check_interval_min'       => $interval,
                        'loop_min_price_move_pct'  => (float) ($model->loop_min_price_move_pct ?? 0),                        
                        'max_concurrent_trades'    => $model->max_concurrent_trades ?? 1,
                        'allow_same_symbol_reentry'=> $model->allow_same_symbol_reentry ?? false,
                        'cooldown_minutes'         => $model->cooldown_minutes ?? 60,
                        'per_trade_alloc_pct'      => $model->per_trade_alloc_pct ?? 0,
                        'max_exposure_pct'         => $model->max_exposure_pct ?? 100,
                        'max_drawdown_pct'         => $model->max_drawdown_pct ?? 0,
                        'max_adds_per_position'    => $model->max_adds_per_position ?? 0,
                        // Optional extra fields you may want to track
                        'equity'                   => $equity,
                        'start_equity'             => (float) ($model->start_equity ?? $equity),
                        'goal'                     => $model->goal_label ?? null,
                        'risk_per_trade'           => (float) ($model->risk_pct ?? 0),
                    ],

                    'account' => [
                        'equity'            => $model->equity,
                        'cash'              => $model->cash,
                        'used_exposure_pct' => round($usedExposurePct, 2),
                        'day_pnl' => round($dayPnl, 2),
                        'drawdown_pct' => round($drawdownPct, 2),
                    ],

                    'open_exposure_pct' => $openExposurePct,
                    'risk_limits'       => $riskLimits,
                    'open_positions'    => $openPositionsState,
                    'recent_trades'     => $recentTradesState,
                    'prices'           => $prices,

                    'daily_plan' => [
                        'trade_date' => optional($dailyPlanModel?->trade_date)->toDateString(),
                        'lane' => 3,
                        'approved_symbols' => $allowedSymbols,
                        'items' => array_values($dailyPlan),
                    ],

                    'full_plan'        => $fullPlan,
                    'allowed_symbols' => $allowedSymbols,

                    'watchlist' => $watchlist,

                    'market_context' => $marketContext,
                                       
                    'recent_actions' => array_map(function ($t) use ($now) {
                        $referenceTime = !empty($t['closed_at']) ? $t['closed_at'] : ($t['opened_at'] ?? $now);

                        return [
                            'ticker' => $t['symbol'] ?? $t['ticker'],
                            'action' => !empty($t['closed_at']) ? 'CLOSE' : 'OPEN',
                            'side' => strtoupper($t['side'] ?? ''),
                            'minutes_ago' => $now->diffInMinutes(Carbon::parse($referenceTime)),
                        ];
                    }, $recentTradesState),               
                                    ];
              
                // 🔥 INSERT HERE
                if (!$this->shouldRunLoop($model, $state)) {
                    ModelLog::create([
                        'ai_model_id' => $model->id,
                        'action'      => 'SKIP',
                        'payload'     => [
                            'reason' => 'Below loop_min_price_move_pct',
                            'threshold' => $model->loop_min_price_move_pct ?? 0,
                            'prices' => $state['prices'] ?? [],
                        ],
                    ]);
                    continue;
                }

               $stateJson = json_encode($state, JSON_PRETTY_PRINT);
               // ------------------------------
               // 3) Build system + user prompts
               // ------------------------------
/*               
               $systemPrompt = <<<TXT
You are an autonomous trading agent managing a single account.
CORE CONCEPTS IN THE STATE:
- open_positions: all currently open trades (symbol, side, qty, avg_price, stop_price, target_price, unrealized_pnl, opened_at).
- daily_plan: the APPROVED trading ideas for today. Each idea typically has:
 - symbol: e.g. "AAPL"
 - direction: "long" or "short"
 - mode: usually "active_on_open" when the idea should be tradable now
 - stop_loss: numeric level for the protective stop
 - take_profit: numeric level for the main target
 - invalid_level: level where the thesis is considered broken
 - entry_zone: text like "near 100" describing the intended entry area
 - max_size_usd: maximum notional size for this idea
 - notes: human explanation of the thesis
INTERPRETING daily_plan (MANDATORY):
- For direction = "long":
 - The idea is to buy the symbol.
 - Use stop_loss as the protective stop level.
 - Use take_profit as the main exit target.
 - Respect invalid_level as the line where the thesis is broken.
- For direction = "short":
 - The idea is to sell/short the symbol, applying the same stop/target/invalid logic.
- For mode = "active_on_open":
 - Treat the idea as immediately tradable during this session.
APPROXIMATING entry_zone:
- When entry_zone is a phrase like "near 100", interpret the number (100) as the intended anchor level.
- Consider price "near 100" if the current price is within roughly 2–3% of that number (for example 97–103).
- If current price is in this zone, the idea is approved, and you do not already hold that symbol, you should usually OPEN a position following the plan (stop at stop_loss, target at take_profit) instead of waiting forever.
EXIT & POSITION MANAGEMENT GUIDELINES:
- For any open position:
 - If price has clearly reached or broken stop_loss or invalid_level, you should CLOSE the position.
 - If price has reached or exceeded take_profit, it is usually correct to CLOSE and realise profit.
- Do not widen stops. If a stop or invalid_level is threatened, exit instead of moving it further away.
- You may mention trailing logic in reasoning, but in JSON you must still provide a concrete stop and target.
POSITION DISCIPLINE:
- Only trade symbols that appear in the state (open_positions, daily_plan, or prices).
- Treat daily_plan as the playbook: manage and execute those strategies; do not invent unrelated trades.
- Never open a new position in a symbol that is already present in open_positions.
- Keep the number of open positions small and focused. If nothing is clearly attractive, prefer HOLD.
- When you OPEN a position, you MUST provide a stop and a target for each order, consistent with the idea in daily_plan.
ALLOWED ACTIONS:
- "HOLD": keep all positions unchanged. When action is "HOLD", orders MUST be [].
- "OPEN": open new positions according to daily_plan and current prices.
- "CLOSE": close existing positions from open_positions.
MANDATORY CONSTRAINT:
You may ONLY place orders for symbols listed in state.allowed_symbols.
Never invent symbols.
If an order symbol is not in state.allowed_symbols, return HOLD with orders [].
OUTPUT FORMAT (STRICT):
- Respond with PURE JSON only, no markdown, no commentary outside JSON.
- Exact shape:
{
 "action": "HOLD" | "OPEN" | "CLOSE",
 "strategy": "short name of current strategy (e.g. 'AAPL trend follow long')",
 "reasoning": "1–3 sentences explaining the decision, including the exit plan if opening or the trigger if closing.",
 "orders": [
   {
     "symbol": "AAPL",
     "side": "BUY" | "SELL",
     "qty": 10,
     "type": "MARKET",
     "stop": 97.0,
     "target": 110.0
   }
 ]
}
- If you decide not to trade, use exactly:
{"action":"HOLD","strategy":"hold_existing","reasoning":"...","orders":[]}
- If action is "OPEN", orders must describe only NEW positions.
- If action is "CLOSE", orders must only describe closing or reducing existing positions from open_positions.
TXT;

$defaultLoopPrompt = <<<TXT
On each tick, manage the account using the current state:
1) Check open_positions and prices:
  - If any open position has clearly hit or gone through its stop_loss or invalid_level from the plan or its own stop_price, you should CLOSE that position.
  - If price has reached or exceeded take_profit for an open position, it is usually correct to CLOSE and realise profit.
2) Then check daily_plan:
  - Focus only on ideas where approved = true and mode = "active_on_open".
  - For each such idea:
    • If direction = "long" and there is NO open long position in that symbol:
      - If the current price is reasonably "near" the intended entry level (for example within a few percent of the level implied by entry_zone like "near 100"),
        you should OPEN a position using stop_loss as the stop and take_profit as the target.
    • If direction = "short" and there is NO open short position in that symbol:
      - Apply the same logic in the short direction.
  - Do NOT open more than one new position in the same symbol at once, and avoid reopening a symbol immediately after closing it unless the plan clearly supports re-entry.
3) If nothing obvious needs to be opened or closed, choose HOLD.
Your reasoning must always:
- Refer to the specific symbol(s) you are acting on.
- Mention stop_loss and take_profit from the plan when you OPEN.
- Mention whether you are closing because of stop_loss, invalid_level, or take_profit when you CLOSE.
TXT;
*/

$systemPrompt = <<<TXT
You are an autonomous trading agent managing a single paper trading account.

You will receive a STATE JSON object.
Use STATE as the single source of truth.

Your job is to make exactly one decision for this cycle:
- OPEN
- CLOSE
- HOLD

You must be adaptive:
- evaluate the current market regime
- respect the approved daily plan
- manage existing positions
- avoid overtrading
- protect capital first

STATE structure you must use:
- state.session: current market phase and minutes since open
- state.model: model rules and limits
- state.account: account equity, cash, exposure, pnl, drawdown
- state.open_positions: currently open positions
- state.daily_plan.approved_symbols: symbols that may be traded
- state.daily_plan.items: today's approved ideas
- state.watchlist: current symbol-level metrics and sizing limits
- state.market_context: trend, volatility, breadth, risk_on
- state.recent_actions: recent opens/closes
- state.allowed_symbols: final hard symbol whitelist

Hard constraints:
1. Only trade symbols listed in state.allowed_symbols.
2. Only open trades in symbols listed in state.daily_plan.approved_symbols.
3. Never open a symbol that is already in state.open_positions.
4. If action is HOLD, orders must be [].
5. Never invent symbols, prices, stops, targets, or quantities.
6. Never open a trade without a defined entry, stop, and target.
7. Never exceed sizing implied by state.watchlist.max_qty.
8. Only use size multipliers from state.watchlist.allowed_size_multipliers.
9. If data is unclear, incomplete, stale, or contradictory, return HOLD.

Adaptive regime logic:
Internally classify each opportunity as one of:
- breakout
- momentum
- trend_follow
- mean_reversion
- vwap_reversion
- gap_and_go
- gap_fade
- no_trade

Plan discipline:
- Treat state.daily_plan.items as the approved playbook.
- Prefer trades that align with the plan item for that symbol.
- Use plan levels when available for entry context, invalidation, and targets.
- Do not invent unrelated setups outside the approved plan.

Exit logic:
- CLOSE when stop or invalidation has clearly failed.
- CLOSE when target has clearly been reached.
- CLOSE when the original thesis no longer matches current market regime.
- CLOSE when de-risking is appropriate based on drawdown, exposure, or degraded quality.

Open logic:
Only OPEN when all are true:
- symbol is approved
- setup quality is high
- regime is clear
- entry, stop, and target are defined
- risk/reward is acceptable
- size is within max_qty
- market context supports the trade

Sizing logic:
For an approved symbol, use the watchlist item to determine size:
- entry_reference
- base_trade_budget
- max_qty
- allowed_size_multipliers

Allowed size multipliers:
- 0.25 = lower conviction
- 0.50 = normal conviction
- 1.00 = highest conviction

Choose 1.00 only for the clearest setups.
If unsure, use smaller size or HOLD.

Quality threshold:
Before opening, internally score setup quality from 0 to 10 using:
- plan alignment
- regime clarity
- entry quality
- stop quality
- target quality
- market cleanliness
- market context

Only OPEN if score is 8 or higher.
Otherwise HOLD.

Output format:
Return PURE JSON only.
No markdown.
No commentary outside JSON.

Exact shape:
{
  "action": "HOLD" | "OPEN" | "CLOSE",
  "strategy": "short strategy name",
  "reasoning": "brief explanation",
  "orders": [
    {
      "symbol": "AAPL",
      "side": "BUY" | "SELL",
      "qty": 10,
      "type": "MARKET",
      "stop": 97.0,
      "target": 110.0
    }
  ]
}

Rules for orders:
- OPEN = only new positions
- CLOSE = only existing open positions
- HOLD = orders must be []

If no action is strong and clear, return:
{"action":"HOLD","strategy":"hold_existing","reasoning":"No high-quality action.","orders":[]}
TXT;

              $defaultLoopPrompt = <<<TXT
On each tick:

1. Review open_positions first.
- Close positions that have clearly hit stop, invalidation, or target.
- Close positions whose thesis no longer fits current regime.

2. Review state.daily_plan.items and state.watchlist.
- Only consider symbols in state.daily_plan.approved_symbols.
- Prefer plan-aligned setups.
- Use watchlist metrics such as:
  - change_from_prev_loop_pct
  - day_change_pct
  - distance_to_entry_pct
  - distance_to_vwap_pct
  - relative_volume
  - intraday_range_pct
  - regime_hint
  - max_qty
  - allowed_size_multipliers

3. OPEN only when:
- setup quality is high
- regime is clear
- symbol is approved
- symbol is not already open
- entry, stop, and target are defined
- qty is reasonable and within max_qty

4. HOLD when nothing is clearly actionable.

Your reasoning must be concise and refer to the specific symbol and trigger.
TXT;
$loopPrompt = $defaultLoopPrompt;
if ($model->loop_prompt_status) {   
  $loopPrompt = $dailyPlanModel ? ($dailyPlanModel->locked_loop_prompt ?? ($model->loop_prompt ?? $defaultLoopPrompt)) : $defaultLoopPrompt;
}
else
{
  $loopPrompt = $dailyPlanModel ? ($dailyPlanModel->locked_loop_prompt ?? $defaultLoopPrompt) : $defaultLoopPrompt;
}

               // Final user prompt: give state + model instructions
               $userPrompt = <<<TXT
Here is your current trading state as JSON:
$stateJson
Instructions for this model:
$loopPrompt
Now, based on the state and instructions, return ONE JSON object with:
- action ("HOLD", "OPEN", or "CLOSE")
- strategy
- reasoning
- orders[]
TXT;
              

                // --- DEBUG: approximate token usage for this tick -----------------
                $promptRaw = $systemPrompt . "\n\n" . $userPrompt;
                $stateRaw  = $stateJson;

                $approxPromptTokens = (int) (strlen($promptRaw) / 4);
                $approxStateTokens  = (int) (strlen($stateRaw) / 4);
                $approxTotalTokens  = $approxPromptTokens + $approxStateTokens;

                ModelLog::create([
                    'ai_model_id' => $model->id,
                    'action'      => 'TICK_TOKEN_DEBUG',
                    'summary'     => 'Approx token usage for this tick',
                    'payload'     => [
                        'prompt_tokens'       => $approxPromptTokens,
                        'state_tokens'        => $approxStateTokens,
                        'total_tokens'        => $approxTotalTokens,
                        'prompt_length_chars' => strlen($promptRaw),
                        'state_length_chars'  => strlen($stateRaw),
                    ],
                ]);
                // -----------------------------------------------------------------
               // ------------------------------
               // 4) CALL THE OPENAI RESPONSES API
               // ------------------------------
               $modelName = config('services.openai.model', 'gpt-4.1-mini');
               $apiKey    = config('services.openai.key');
               if (empty($apiKey)) {
                   throw new \RuntimeException('OPENAI_API_KEY is not set.');
               }

              $attempts = 0;
              $maxAttempts = 5;
              $delay = 1; // seconds

              do {
               $response = Http::withToken($apiKey)
                   ->timeout(20)
                   ->post('https://api.openai.com/v1/responses', [
                       'model' => $modelName,
                       'input' => [
                           [
                               'role'    => 'system',
                               'content' => [
                                   [
                                       'type' => 'input_text',
                                       'text' => $systemPrompt,
                                   ],
                               ],
                           ],
                           [
                               'role'    => 'user',
                               'content' => [
                                   [
                                       'type' => 'input_text',
                                       'text' => $userPrompt,
                                   ],
                               ],
                           ],
                       ],
                        'max_output_tokens' => 1024,
                   ]);
                   
                  // If NOT rate-limited, exit loop
                  if ($response->status() !== 429) {                      
                      break;
                  }

                  if ($response->status() === 429) {
                      ModelLog::create([
                          'ai_model_id' => $model->id,
                          'action'      => 'OPENAI_429_DEBUG',
                          'payload'     => [
                              'body' => $response->json(),
                              'headers' => [
                                  'x-ratelimit-limit-requests' => $response->header('x-ratelimit-limit-requests'),
                                  'x-ratelimit-remaining-requests' => $response->header('x-ratelimit-remaining-requests'),
                                  'x-ratelimit-reset-requests' => $response->header('x-ratelimit-reset-requests'),
                                  'x-ratelimit-limit-tokens' => $response->header('x-ratelimit-limit-tokens'),
                                  'x-ratelimit-remaining-tokens' => $response->header('x-ratelimit-remaining-tokens'),
                                  'x-ratelimit-reset-tokens' => $response->header('x-ratelimit-reset-tokens'),
                                  'retry-after' => $response->header('retry-after'),
                              ],
                          ],
                      ]);
                  }

                  // Hit rate limit → wait + backoff
                  sleep($delay);
                  $delay *= 2; // 1s → 2s → 4s → 8s
                  $attempts++;

              } while ($attempts < $maxAttempts);

               if (!$response->successful()) {
                   throw new \RuntimeException(
                       'OpenAI API error: ' . $response->status() . ' ' . $response->body()
                   );
               }
               $body = $response->json();
               // Responses API: output[0].content[] where each item has type=output_text
               $outputText = '';
               if (!empty($body['output'][0]['content'])) {
                   foreach ($body['output'][0]['content'] as $chunk) {
                       if (($chunk['type'] ?? null) === 'output_text') {
                           $outputText .= $chunk['text'] ?? '';
                       }
                   }
               }
               $outputText = trim($outputText);
               if ($outputText === '') {
                   throw new \RuntimeException('Empty response from OpenAI Responses API.');
               }
               // ------------------------------
               // 5) Parse JSON safely
               // ------------------------------
               $decision = AiDecisionParser::parse($outputText);

               // ------------------------------
               // 6) Save log (Model Chat uses this)
               // ------------------------------
              $strategyField = $decision['strategy'] ?? null;
              if (is_array($strategyField)) {
                 $strategyName = $strategyField['name'] ?? json_encode($strategyField);
              } else {
                 $strategyName = $strategyField;
              }

               $log = new ModelLog();
               $log->ai_model_id = $model->id;
               $log->action      = $decision['action'];
               $log->payload = [
                   'strategy'  => [
                       //'name' => $decision['strategy'],
                      'name' => $strategyName,
                   ],
                   'reasoning' => $decision['reasoning'],
                   'orders'    => $decision['orders'],
                   'raw'       => [
                       'response' => json_decode($decision['raw_json'], true),
                       'prompt'   => $userPrompt,
                   ],
               ];
               $log->save();       

               if (($decision['action'] ?? null) === 'OPEN' && !empty($decision['orders'][0])) {
                    $order = &$decision['orders'][0];
                    $symbol = strtoupper($order['symbol'] ?? '');

                    $watch = collect($state['watchlist'])->firstWhere('ticker', $symbol);

                    if (!$watch) {
                        $decision = [
                            'action' => 'HOLD',
                            'strategy' => 'hold_existing',
                            'reasoning' => 'Order symbol not found in watchlist.',
                            'orders' => [],
                        ];
                    } else {
                        $maxQty = (int) ($watch['max_qty'] ?? 0);
                        $qty = (int) ($order['qty'] ?? 0);

                        if ($qty < 1 || $qty > $maxQty) {
                            $order['qty'] = max(0, min($qty, $maxQty));
                        }

                        if (($order['qty'] ?? 0) < 1) {
                            $decision = [
                                'action' => 'HOLD',
                                'strategy' => 'hold_existing',
                                'reasoning' => 'Computed quantity below minimum.',
                                'orders' => [],
                            ];
                        }
                    }
                }

               // ------------------------------
              // 7) Apply orders via PaperBroker (positions + trades + equity)
              // ------------------------------
              $broker->processDecision($model, $decision);
              // ------------------------------
              // 8) Snapshot equity AFTER broker updates it
              // ------------------------------
              EquitySnapshot::create([
                 'ai_model_id' => $model->id,
                 'equity'      => $model->equity,
                 'taken_at'    => now(),
              ]);
              $model->last_checked_at = now();
              $model->save();              
           } catch (\Throwable $e) {
               $this->error("Error in model {$model->name}: " . $e->getMessage());
             
               ModelLog::create([
                   'ai_model_id' => $model->id,
                   'action'      => 'ERROR',
                   'payload'     => [
                       'error' => $e->getMessage(),                      
                        'file'    => $e->getFile(),
                        'line'    => $e->getLine(),
                        'trace'   => $e->getTraceAsString(),
                   ],
               ]);
               continue;
           }
       }
       return self::SUCCESS;
   }

    protected function shouldRunLoop($model, $state): bool
    {
        $threshold = $model->loop_min_price_move_pct ?? 0;

        if ($threshold <= 0) {
            return true;
        }

        // Example: compare last vs previous price
        foreach ($state['prices'] ?? [] as $symbol => $data) {
            if (!isset($data['last'], $data['prev'])) {
                continue;
            }

            $last = $data['last'];
            $prev = $data['prev'];

            if ($prev <= 0) {
                continue;
            }

            $movePct = abs(($last - $prev) / $prev) * 100;

            if ($movePct >= $threshold) {
                return true; // enough movement → run AI
            }
        }

        return false; // no meaningful movement → skip
    }
}
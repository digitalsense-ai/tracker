<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
//     $marketData = app(\App\Services\MarketData::class);
//     $test_price = $marketData->getPrice('AAPL');
// dd($test_price);
      $now = Carbon::now();
      
      $query = AiModel::where('active', true);
      if ($this->option('model_id')) {
        $query->where('id', $this->option('model_id'));
      }
      $models = $query->get();

       $broker = app(PaperBroker::class);

       foreach ($models as $model) {
           try {
//             Log::info('OpenAI debug', [
//   'env' => app()->environment(),
//   'model' => config('services.openai.model'),
//   'key_last4' => substr(config('services.openai.key'), -4),
// ]);

               // ------------------------------
               // 1) Respect check interval
               // ------------------------------
            /*
               if (!empty($model->last_checked_at)) {
                   $last = $model->last_checked_at instanceof Carbon
                       ? $model->last_checked_at
                       : Carbon::parse($model->last_checked_at);
                   $interval = $model->check_interval ?? $model->check_interval_min ?? 1; // fallback
                   $nextCheck = $last->copy()->addMinutes($interval);                   
                   if ($now->lt($nextCheck)) {
                       // Skip this model this minute
                       continue;
                   }
               }
               */

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

                // // Always define prices before state
                // $prices = [];
                // $marketData = app(\App\Services\MarketData::class);
                // foreach ($openPositions as $p) {
                //   $ticker = $p->ticker;
                //   if (!isset($prices[$ticker])) {
                //     $prices[$ticker] = [
                //       'last' => (float) $marketData->getPrice($ticker),
                //     ];
                //   }
                // }

                // Load today\'s daily plan if it exists
                //$today = now()->toDateString();
                $dailyPlanModel = AiDailyPlan::where('ai_model_id', $model->id)
                   //->where('trade_date', $today)
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
                $allowedSymbols = collect($openPositionsState)->pluck('symbol')
                    ->merge(collect($dailyPlan)->pluck('symbol'))
                    ->map(fn ($s) => strtoupper($s))
                    ->unique()
                    ->values()
                    ->all();             

                // Attach current prices for any relevant symbols (open positions + plan)
                $prices = [];
                $marketData = app(\App\Services\MarketData::class);

                foreach ($openPositions as $p) {
                    $ticker = $p->ticker;
                    if ($ticker && !isset($prices[$ticker])) {
                        $prices[$ticker] = [
                            'last' => (float) $marketData->getPrice($ticker),
                        ];
                    }
                }

                foreach ($dailyPlan as $s) {
                    if (!is_array($s)) {
                        continue;
                    }
                    $ticker = $s['symbol'] ?? null;
                    if ($ticker) {
                        $ticker = strtoupper($ticker);
                        if (!isset($prices[$ticker])) {
                            $prices[$ticker] = [
                                'last' => (float) $marketData->getPrice($ticker),
                            ];
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
                
               // Build state object for the AI
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

               // // Attach current prices for symbols (if available)
               // $prices = [];
               // $marketData = app(\App\Services\MarketData::class);
               // foreach ($openPositions as $p) {
               //     $ticker = $p->ticker;
               //     if (!isset($prices[$ticker])) {
               //         $prices[$ticker] = [
               //             'last' => (float) $marketData->getPrice($ticker),
               //         ];
               //     }
               // }

               // // Load today\'s daily plan if it exists
               // $today = now()->toDateString();
               // $dailyPlanModel = AiDailyPlan::where('ai_model_id', $model->id)
               //     ->where('trade_date', $today)
               //     ->first();
               // $dailyPlan = $dailyPlanModel ? $dailyPlanModel->plan_json : null;
               $stateJson = json_encode($state, JSON_PRETTY_PRINT);
               // ------------------------------
               // 3) Build system + user prompts
               // ------------------------------
//                $systemPrompt = <<<TXT
// You are an autonomous trading agent managing a single account.
// HARD REQUIREMENTS (DO NOT VIOLATE):
// You may see a 'prices' map and a 'daily_plan' array in the state:
// - Use 'prices' to understand current market levels.
// - Use 'daily_plan' as the playbook: manage and execute those strategies instead of inventing completely new ones.
// - Only trade symbols given in the state.
// - Never exceed max_concurrent_positions.
// - Never exceed max_exposure_pct of account equity.
// - Do not open a new position in the same symbol if allow_same_symbol_reentry = false and it is already open.
// - Respect per_trade_alloc_pct as the maximum notional size for a single new position.
// - Only use these actions: "HOLD", "OPEN", "CLOSE".
// - When action is "HOLD", orders MUST be an empty array.
// OUTPUT FORMAT (MUST FOLLOW EXACTLY):
// - Respond with PURE JSON only, no markdown, no extra commentary.
// - Shape:
// {
//  "action": "HOLD" | "OPEN" | "CLOSE",
//  "strategy": "short name of current strategy (e.g. 'opening range breakout')",
//  "reasoning": "1-3 sentences explaining your decision, referencing the state",
//  "orders": [
//    {
//      "symbol": "AAPL",
//      "side": "BUY" | "SELL",
//      "qty": 10,
//      "type": "MARKET",
//      "stop": 185.0,
//      "target": 192.0
//    }
//  ]
// }
// - If you decide not to trade, use:
// {"action":"HOLD","strategy":"...","reasoning":"...","orders":[]}
// - If you decide to CLOSE, orders must only describe closing existing positions in the state.
// TXT;

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

               // Model-specific instructions from DB (what you put in "loop_prompt")
//               $loopPrompt = 'Decide whether to hold, open, or close positions.';
//               if($model->loop_prompt_status)
//                 $loopPrompt = $model->loop_prompt ?? 'Decide whether to hold, open, or close positions.';
              

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
$loopPrompt = $defaultLoopPrompt;
if ($model->loop_prompt_status) {
   $loopPrompt = $model->loop_prompt ?? $defaultLoopPrompt;
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

              // // ------------------------------
              // // 5.5) Guardrails + risk sizing (RESTORED FROM OLD TICK)
              // // ------------------------------
              // $guards = app(\App\Services\Guards\Guardrails::class);
              // $risk   = app(\App\Services\RiskManager::class);

              // // Validate required schema fields
              // $required = ['action','orders','strategy','reasoning'];
              // $missing  = [];
              // foreach ($required as $k) {
              //     if (!array_key_exists($k, $decision)) $missing[] = $k;
              // }

              // if (!empty($missing)) {
              //     ModelLog::create([
              //         'ai_model_id' => $model->id,
              //         'action'      => 'HOLD',
              //         'payload'     => [
              //             'error'   => 'invalid_response',
              //             'missing' => $missing,
              //             'raw'     => $decision,
              //         ],
              //     ]);
              //     $model->last_checked_at = now();
              //     $model->save();
              //     continue;
              // }

              // // Apply guardrails
              // [$ok, $violations, $computed] = $guards->validate($model, $decision);

              // if (!$ok) {
              //     ModelLog::create([
              //         'ai_model_id' => $model->id,
              //         'action'      => 'HOLD',
              //         'payload'     => [
              //             'guardrails' => 'blocked',
              //             'violations' => $violations,
              //             'computed'   => $computed,
              //             'decision'   => $decision,
              //         ],
              //     ]);
              //     $model->last_checked_at = now();
              //     $model->save();
              //     continue;
              // }

              // // Risk sizing for orders (RESTORED)
              // $orders = $decision['orders'] ?? [];
              // foreach ($orders as &$o) {
              //     $o = $risk->size($model, $o);
              // }
              // unset($o);


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
               // // ------------------------------
               // // 7) Execute orders (OPEN/CLOSE)
               // // ------------------------------
               // if (in_array($decision['action'], ['OPEN', 'CLOSE'])) {
               //     foreach ($decision['orders'] as $order) {
               //         $symbol = $order['symbol'] ?? null;
               //         $side   = strtoupper($order['side'] ?? 'BUY');
               //         $qty    = (float)($order['qty'] ?? 0);
               //         if (!$symbol || $qty <= 0) {
               //             continue;
               //         }
               //         $trade = new Trade();
               //         $trade->ai_model_id = $model->id;
               //         $trade->symbol      = $symbol;
               //         $trade->side        = $side;
               //         $trade->qty         = $qty;
               //         $trade->status      = 'open';
               //         $trade->opened_at   = now();
               //         $trade->entry_price = 0; // replace with real feed/webhook price
               //         $trade->save();
               //     }
               // }

              // ------------------------------
              // 7) Broker execution (RESTORED LOGIC + NEW LOGIC)
              // ------------------------------
              // if (!empty($orders)) {
              //     // Maintain compatibility with the new system: processDecision()
              //     // but pass the sized orders explicitly
              //     //$broker->execute($model, $orders);
              //     $decision['orders'] = $orders;
              // }

              //$broker->processDecision($model, $decision);

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

               // // ------------------------------
               // // 8) Update equity + snapshot
               // // ------------------------------
               // // TODO: update $model->equity based on trades / PnL logic
               // EquitySnapshot::create([
               //     'ai_model_id' => $model->id,
               //     'equity'      => $model->equity,
               //     'taken_at'    => now(),
               // ]);
               // $model->last_checked_at = now();
               // $model->save();
           } catch (\Throwable $e) {
               $this->error("Error in model {$model->name}: " . $e->getMessage());

               // $model->last_checked_at = now();
               // $model->save();
               
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
}
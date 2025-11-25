<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

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
   protected $signature = 'ai:tick';
   protected $description = 'Runs one tick for all active AI trading models';
   public function handle()
   {
       $now = Carbon::now();
       $models = AiModel::where('active', true)->get();
       $broker = app(PaperBroker::class);

       foreach ($models as $model) {
           try {
               // ------------------------------
               // 1) Respect check interval
               // ------------------------------
               if (!empty($model->last_checked_at)) {
                   $last = $model->last_checked_at instanceof Carbon
                       ? $model->last_checked_at
                       : Carbon::parse($model->last_checked_at);
                   $interval = $model->check_interval ?? $model->check_interval_min ?? 1; // fallback
                   $nextCheck = $last->copy()->addMinutes($interval);     
                   //dd($now, $nextCheck);
                   if ($now->lt($nextCheck)) {
                       // Skip this model this minute
                       continue;
                   }
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
                   ->limit(10) //20
                   ->get();
               // Compute current exposure (very simple version)
               $equity = (float) ($model->equity ?? 0);
               $grossExposure = 0.0;
               foreach ($openPositions as $p) {
                   $notional = (float) $p->qty * (float) $p->avg_price;
                   $grossExposure += abs($notional);
               }
               $openExposurePct = $equity > 0 ? ($grossExposure / $equity) * 100.0 : 0.0;
               // // Risk / guardrail fields (adjust names if your columns differ)
               // $riskLimits = [
               //     'max_concurrent_positions'   => (int)   ($model->max_concurrent_trades ?? 5),
               //     'allow_same_symbol_reentry'  => (bool)  ($model->allow_same_symbol_reentry ?? false),
               //     'cooldown_minutes'           => (int)   ($model->cooldown_minutes ?? 0),
               //     'per_trade_alloc_pct'        => (float) ($model->per_trade_alloc_pct ?? 20),
               //     'max_exposure_pct'           => (float) ($model->max_exposure_pct ?? 80),
               //     'max_leverage'               => (float) ($model->max_leverage ?? 5),
               //     'max_drawdown_pct'           => (float) ($model->max_drawdown_pct ?? 0),
               // ];
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

                // Always define prices before state
                $prices = [];
                $marketData = app(\App\Services\MarketData::class);
                foreach ($openPositions as $p) {
                  $ticker = $p->ticker;
                  if (!isset($prices[$ticker])) {
                    $prices[$ticker] = [
                      'last' => (float) $marketData->getPrice($ticker),
                    ];
                  }
                }

                // Load today\'s daily plan if it exists
                $today = now()->toDateString();
                $dailyPlanModel = AiDailyPlan::where('ai_model_id', $model->id)
                   ->where('trade_date', $today)
                   ->first();
                $dailyPlan = $dailyPlanModel ? $dailyPlanModel->plan_json : null;
               
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
                   //'risk_limits'       => $riskLimits,
                   'open_positions'    => $openPositionsState,
                   'recent_trades'     => $recentTradesState,
                   'prices'           => $prices,
                   'daily_plan'       => $dailyPlan,
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
               $systemPrompt = <<<TXT
You are an autonomous trading agent managing a single account.
HARD REQUIREMENTS (DO NOT VIOLATE):
You may see a 'prices' map and a 'daily_plan' array in the state:
- Use 'prices' to understand current market levels.
- Use 'daily_plan' as the playbook: manage and execute those strategies instead of inventing completely new ones.
- Only trade symbols given in the state.
- Never exceed max_concurrent_positions.
- Never exceed max_exposure_pct of account equity.
- Do not open a new position in the same symbol if allow_same_symbol_reentry = false and it is already open.
- Respect per_trade_alloc_pct as the maximum notional size for a single new position.
- Only use these actions: "HOLD", "OPEN", "CLOSE".
- When action is "HOLD", orders MUST be an empty array.
OUTPUT FORMAT (MUST FOLLOW EXACTLY):
- Respond with PURE JSON only, no markdown, no extra commentary.
- Shape:
{
 "action": "HOLD" | "OPEN" | "CLOSE",
 "strategy": "short name of current strategy (e.g. 'opening range breakout')",
 "reasoning": "1-3 sentences explaining your decision, referencing the state",
 "orders": [
   {
     "symbol": "AAPL",
     "side": "BUY" | "SELL",
     "qty": 10,
     "type": "MARKET",
     "stop": 185.0,
     "target": 192.0
   }
 ]
}
- If you decide not to trade, use:
{"action":"HOLD","strategy":"...","reasoning":"...","orders":[]}
- If you decide to CLOSE, orders must only describe closing existing positions in the state.
TXT;
               // Model-specific instructions from DB (what you put in "loop_prompt")
               $loopPrompt = $model->loop_prompt ?? 'Decide whether to hold, open, or close positions.';
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
              
              // --- DEBUG: TOKEN APPROXIMATION -------------------------------------
              $promptRaw  = $systemPrompt . "\n\n" . $userPrompt;
              $stateRaw   = json_encode($state);
              $approxPromptTokens = intval(strlen($promptRaw) / 4);
              $approxStateTokens  = intval(strlen($stateRaw) / 4);
              $approxTotalTokens  = $approxPromptTokens + $approxStateTokens;
              ModelLog::create([
                 'ai_model_id' => $model->id,
                 'action'      => 'TICK_TOKEN_DEBUG',
                 'summary'     => "Approx token usage for this tick",
                 'payload'     => [
                     'prompt_tokens' => $approxPromptTokens,
                     'state_tokens'  => $approxStateTokens,
                     'total_tokens'  => $approxTotalTokens,
                     'prompt_length_chars' => strlen($promptRaw),
                     'state_length_chars'  => strlen($stateRaw),
                 ],
              ]);
              // ----------------------------------------------------------------------

               // ------------------------------
               // 4) CALL THE OPENAI RESPONSES API
               // ------------------------------
               $modelName = config('services.openai.model', 'gpt-4.1-mini');
               $apiKey    = config('services.openai.key');
               if (empty($apiKey)) {
                   throw new \RuntimeException('OPENAI_API_KEY is not set.');
               }
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
                       'max_output_tokens' => 512,
                   ]);
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
               $log = new ModelLog();
               $log->ai_model_id = $model->id;
               $log->action      = $decision['action'];
               $log->payload = [
                   'strategy'  => [
                       'name' => $decision['strategy'],
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

               // ------------------------------
               // 8) Update equity + snapshot
               // ------------------------------
               // TODO: update $model->equity based on trades / PnL logic
               EquitySnapshot::create([
                   'ai_model_id' => $model->id,
                   'equity'      => $model->equity,
                   'taken_at'    => now(),
               ]);
               $model->last_checked_at = now();
               $model->save();
           } catch (\Throwable $e) {
               $this->error("Error in model {$model->name}: " . $e->getMessage());

               $model->last_checked_at = now();
               $model->save();
               
               ModelLog::create([
                   'ai_model_id' => $model->id,
                   'action'      => 'ERROR',
                   'payload'     => [
                       'error' => $e->getMessage(),
                   ],
               ]);
               continue;
           }
       }
       return self::SUCCESS;
   }
}
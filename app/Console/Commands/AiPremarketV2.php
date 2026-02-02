<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\AiModel;
use App\Models\AiDailyPlan;
use App\Models\ModelLog;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AiPremarketV2 extends Command
{
    protected $signature = 'ai:premarket-v2 {--model_id=}';
    protected $description = 'Generates a price-anchored v2 daily plan (playbook)';

    public function handle()
    {
        $tradeDate = now()->toDateString();

        $query = AiModel::where('active', true);
        if ($this->option('model_id')) {
            $query->where('id', $this->option('model_id'));
        }
        $models = $query->get();

        $marketData = app(\App\Services\MarketData::class);

        foreach ($models as $model) {
            // 1) Open positions (context)
            $openPositions = Position::where('ai_model_id', $model->id)
                ->where('status', 'open')
                ->get()
                ->map(fn(Position $p) => [
                    'ticker' => $p->ticker,
                    'side' => $p->side,
                    'qty' => (float)$p->qty,
                    'avg_price' => (float)$p->avg_price,
                    'stop_price' => $p->stop_price !== null ? (float)$p->stop_price : null,
                    'target_price' => $p->target_price !== null ? (float)$p->target_price : null,
                ])->values()->toArray();

            // 2) Universe (IMPORTANT)
            // Replace this with your own watchlist source (DB/config).
            // $universe = array_values(array_unique(array_filter([
            //     'AAPL','MSFT','GOOG','AMZN','TSLA','NVDA','SPY','QQQ'
            // ])));

            $universe = app(\App\Services\SymbolUniverse::class)->candidates(
               $model->symbol_limit ?? 20
            );

            // 3) Fetch LIVE prices for universe
            $prices = [];
            foreach ($universe as $sym) {
                $last_price = (float) $marketData->getPrice($sym);
                
                $prices[$sym] = ['last' => $last_price];

                $this->info("Pre-market price for symbol {$sym} - {$last_price}");
            }

            $state = [
                'date' => $tradeDate,
                'time' => Carbon::now()->toIso8601String(),
                'equity' => (float) ($model->equity ?? 0),
                'open_positions' => $openPositions,
                'prices' => $prices,
                'settings' => [
                    'max_strategies_per_day' => (int)($model->max_strategies_per_day ?? 3),
                    'max_symbols_per_day' => (int)($model->max_symbols_per_day ?? 3),
                    'allow_sleeper_strategies' => (bool)($model->allow_sleeper_strategies ?? true),
                    'default_risk_per_strategy_pct' => (float)($model->default_risk_per_strategy_pct ?? 0.5),
                ],
            ];

            $stateJson = json_encode($state, JSON_PRETTY_PRINT);

            $systemPrompt = <<<TXT
You are the pre-market planner for an autonomous trading model.
You output ONLY a JSON ARRAY of plan items.

CRITICAL REQUIREMENT:
- The state includes a "prices" map with LIVE prices.
- You MUST anchor ALL numeric levels (entry/stop/targets/invalidation) around the live price.
- If you do not have a price for a symbol, DO NOT include that symbol.

V2 PLAN ITEM SCHEMA (REQUIRED):
Each item MUST include:
- plan_item_id (uuid)
- status ("idea_pool" | "approved" | "activated" | "closed" | "stale") -> planner uses "approved" or "idea_pool"
- planned_at (ISO8601)
- price_at_plan_time (number)
- symbol, direction, type, mode, priority, notes
- entry: { type, zone_low, zone_high, valid_until }
- exit_plan: { stop_loss, invalidation, targets[], time_stop_minutes, trailing }
- max_size_usd, risk_pct

RULES:
- active_on_open: entry zone must be within ~0-3% of current price.
- sleeper: may be farther away but must be explicit and realistic.
- Keep to max strategies / symbols in settings.

Return ONLY the JSON array. No markdown.
TXT;

            $premarketPrompt = '';
            if ($model->premarket_prompt_status) {
                $premarketPrompt = $model->premarket_prompt ?? '';
            }

            $userPrompt = <<<TXT
Here is the current planning state as JSON:
{$stateJson}

Additional planning instructions:
{$premarketPrompt}
TXT;

            $apiKey = config('services.openai.key');

            $attempts = 0;
            $maxAttempts = 5;
            $delay = 5; // seconds

            do {
                $response = Http::withToken($apiKey)->post('https://api.openai.com/v1/responses', [
                    'model' => config('services.openai.model', 'gpt-4.1-mini'),
                    'input' => [
                        [
                            'role' => 'system',
                            'content' => [['type' => 'input_text', 'text' => $systemPrompt]],
                        ],
                        [
                            'role' => 'user',
                            'content' => [['type' => 'input_text', 'text' => $userPrompt]],
                        ],
                    ],
                    'max_output_tokens' => 2048,
                ]);

                // If NOT rate-limited, exit loop
                if ($response->status() !== 429) {
                    break;
                }

                // Hit rate limit → wait + backoff
                sleep($delay);
                $delay *= 2; // 1s → 2s → 4s → 8s
                $attempts++;

            } while ($attempts < $maxAttempts);

            if (!$response->successful()) {
                throw new \RuntimeException('OpenAI error: '.$response->body());
            }

            $body = $response->json();
            $out = '';
            foreach (($body['output'][0]['content'] ?? []) as $chunk) {
                if (($chunk['type'] ?? null) === 'output_text') $out .= ($chunk['text'] ?? '');
            }
            $out = trim($out);

            $plan = json_decode($out, true);
            if (!is_array($plan)) throw new \RuntimeException('Premarket did not return valid JSON array.');

            // Normalize: ensure plan_item_id, planned_at, price_at_plan_time
            foreach ($plan as &$item) {
                if (!is_array($item)) continue;
                $item['plan_item_id'] = $item['plan_item_id'] ?? (string) Str::uuid();
                $item['planned_at'] = $item['planned_at'] ?? Carbon::now()->toIso8601String();
                $sym = strtoupper($item['symbol'] ?? '');
                $item['price_at_plan_time'] = $item['price_at_plan_time'] ?? ($prices[$sym]['last'] ?? null);
            }
            unset($item);

            AiDailyPlan::updateOrCreate(
                ['ai_model_id' => $model->id, 'trade_date' => $tradeDate],
                ['plan_json' => $plan]
            );

            ModelLog::create([
                'ai_model_id' => $model->id,
                'action' => 'PREMARKET_PLAN_V2',
                'payload' => ['date' => $tradeDate, 'state' => $state, 'plan' => $plan],
            ]);
        }

        return self::SUCCESS;
    }
}

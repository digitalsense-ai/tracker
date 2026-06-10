<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\AiModel;
use App\Models\AiDailyPlan;
use App\Models\ModelLog;
use App\Models\Position;
use App\Models\SymbolMapping;

use Carbon\Carbon;
use Illuminate\Support\Str;

class AiPremarketV2 extends Command
{
    protected $signature = 'ai:premarket-v2 {--model_id=} {--region=}';
    protected $description = 'Generates a price-anchored v2 daily plan (playbook)';

    public function handle()
    {
        $region = $this->option('region');

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

            // $universe = app(\App\Services\SymbolUniverse::class)->candidates(
            //    $model->symbol_limit ?? 20
            // );

            $universe = SymbolMapping::where('enabled_for_ai',true)
                           ->where('region', $region)
                           ->orderBy('priority')
                           ->limit(($model->symbol_limit ?? 20))
                           ->pluck('symbol')
                           ->toArray();    

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
                'allowed_symbols' => array_keys($prices),
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
- You may return either the flat plan schema or the nested V2 plan schema.
- The backend will normalize both schemas before saving.

CANONICAL FLAT PLAN ITEM SCHEMA:
Each saved item is normalized to include:
- plan_item_id, status, approved, planned_at, price_at_plan_time
- symbol, direction, type, mode, priority, notes
- entry_zone, entry_zone_low, entry_zone_high
- stop_loss, take_profit, invalid_level
- max_size_usd, risk_pct

NESTED V2 PLAN ITEM SCHEMA ALSO ACCEPTED:
- entry: { type, zone_low, zone_high, valid_until }
- exit_plan: { stop_loss, invalidation, targets[], time_stop_minutes, trailing }

RULES:
- active_on_open: entry zone must be within ~0-3% of current price.
- sleeper: may be farther away but must be explicit and realistic.
- Keep to max strategies / symbols in settings.
- Treat default_risk_per_strategy_pct as a percent value: 1.0 means 1%, so risk_pct should be 1.0 and max_size_usd should use equity * (risk_pct / 100).

Return ONLY the JSON array. No markdown.
TXT;

            $premarketPrompt = '';
            if ($model->premarket_prompt_status) {
                $premarketPrompt = $model->premarket_prompt ?? '';
            }

            $premarketPrompt = str_replace(
                '{{region}}',
                strtoupper($region), // or just $region if you prefer raw "EU"
                $premarketPrompt
            );

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

                // Hit rate limit -> wait + backoff
                sleep($delay);
                $delay *= 2; // 1s -> 2s -> 4s -> 8s
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

            // Normalize both accepted schemas into the flat schema expected by AiTick.
            foreach ($plan as &$item) {
                if (!is_array($item)) continue;

                $sym = strtoupper($item['symbol'] ?? $item['ticker'] ?? '');
                $item['symbol'] = $sym;

                $priceAtPlanTime = $item['price_at_plan_time'] ?? ($prices[$sym]['last'] ?? null);
                $entry = is_array($item['entry'] ?? null) ? $item['entry'] : [];
                $exitPlan = is_array($item['exit_plan'] ?? null) ? $item['exit_plan'] : [];
                $targets = is_array($exitPlan['targets'] ?? null) ? $exitPlan['targets'] : [];

                $item['plan_item_id'] = $item['plan_item_id'] ?? (string) Str::uuid();
                $item['planned_at'] = $item['planned_at'] ?? Carbon::now()->toIso8601String();
                $item['price_at_plan_time'] = $priceAtPlanTime;

                $status = $item['status'] ?? null;
                $item['status'] = $status ?? (!empty($item['approved']) ? 'approved' : 'approved');
                $item['approved'] = array_key_exists('approved', $item)
                    ? (bool) $item['approved']
                    : (($item['status'] ?? null) === 'approved');

                $item['direction'] = strtolower($item['direction'] ?? 'long');
                $item['type'] = $item['type'] ?? ($item['strategy'] ?? null);
                $item['mode'] = $item['mode'] ?? 'sleeper';
                $item['priority'] = isset($item['priority']) ? (int) $item['priority'] : 1;

                $item['entry_zone_low'] = $this->firstNumeric(
                    $item['entry_zone_low'] ?? null,
                    $entry['zone_low'] ?? null,
                    $entry['low'] ?? null
                );
                $item['entry_zone_high'] = $this->firstNumeric(
                    $item['entry_zone_high'] ?? null,
                    $entry['zone_high'] ?? null,
                    $entry['high'] ?? null
                );

                $item['stop_loss'] = $this->firstNumeric(
                    $item['stop_loss'] ?? null,
                    $exitPlan['stop_loss'] ?? null
                );
                $item['invalid_level'] = $this->firstNumeric(
                    $item['invalid_level'] ?? null,
                    $exitPlan['invalidation'] ?? null,
                    $exitPlan['invalid_level'] ?? null,
                    $item['stop_loss'] ?? null
                );
                $item['take_profit'] = $this->firstNumeric(
                    $item['take_profit'] ?? null,
                    $item['target_1'] ?? null,
                    $targets[0] ?? null
                );

                $riskPct = $this->firstNumeric(
                    $item['risk_pct'] ?? null,
                    $item['risk_percent'] ?? null,
                    $state['settings']['default_risk_per_strategy_pct'] ?? null
                );
                $item['risk_pct'] = $riskPct;

                if (!isset($item['max_size_usd']) || !is_numeric($item['max_size_usd'])) {
                    $equity = (float) ($state['equity'] ?? 0);
                    $item['max_size_usd'] = ($equity > 0 && $riskPct !== null)
                        ? round($equity * ((float) $riskPct / 100), 2)
                        : 0;
                }

                $item['entry_zone'] = $item['entry_zone']
                    ?? ($entry['type'] ?? 'Price-anchored setup');
                $item['notes'] = $item['notes'] ?? '';
            }
            unset($item);

            AiDailyPlan::updateOrCreate(
                ['ai_model_id' => $model->id, 'trade_date' => $tradeDate, 'region' => $region],
                ['plan_json' => $plan]
            );

            ModelLog::create([
                'ai_model_id' => $model->id,
                'region' => $region,
                'action' => 'PREMARKET_PLAN_V2_' . $region,
                'payload' => ['date' => $tradeDate, 'region' => $region, 'state' => $state, 'plan' => $plan],
            ]);
        }

        return self::SUCCESS;
    }

    private function firstNumeric(...$values): ?float
    {
        foreach ($values as $value) {
            if ($value !== null && is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }
}

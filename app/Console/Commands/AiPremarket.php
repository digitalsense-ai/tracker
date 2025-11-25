<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\AiModel;
use App\Models\AiDailyPlan;
use App\Models\ModelLog;
use App\Models\Position;
use Carbon\Carbon;

class AiPremarket extends Command
{
    protected $signature = 'ai:premarket {--model_id=} {--date=}';
    protected $description = 'Run pre-market planning for AI models and store daily strategy plans';

    public function handle(): int
    {
        $modelId = $this->option('model_id');
        $tradeDate = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::today()->toDateString();

        $query = AiModel::query()->where('active', true);
        if ($modelId) {
            $query->where('id', (int) $modelId);
        }

        $models = $query->get();
        if ($models->isEmpty()) {
            $this->info('No active AI models found.');
            return self::SUCCESS;
        }

        foreach ($models as $model) {
            if (empty($model->premarket_prompt)) {
                $this->info("Skipping model {$model->id} (no premarket_prompt set).");
                continue;
            }

            try {
                $this->info("Running pre-market plan for model {$model->id} ({$model->name}) on {$tradeDate}...");

                // Build a lightweight state snapshot for planning
                $openPositions = Position::where('ai_model_id', $model->id)
                    ->where('status', 'open')
                    ->get()
                    ->map(function (Position $p) {
                        return [
                            'ticker'      => $p->ticker,
                            'side'        => $p->side,
                            'qty'         => $p->qty,
                            'avg_price'   => $p->avg_price,
                            'stop_price'  => $p->stop_price,
                            'target_price'=> $p->target_price,
                            'leverage'    => $p->leverage,
                            'margin'      => $p->margin,
                            'unrealized_pnl' => $p->unrealized_pnl,
                        ];
                    })
                    ->values()
                    ->toArray();

                $state = [
                    'date'    => $tradeDate,
                    'time'    => Carbon::now()->toIso8601String(),
                    'equity'  => (float) ($model->equity ?? 0),
                    'open_positions' => $openPositions,
                    'settings' => [
                        'max_strategies_per_day'        => $model->max_strategies_per_day,
                        'max_symbols_per_day'           => $model->max_symbols_per_day,
                        'allow_sleeper_strategies'      => $model->allow_sleeper_strategies,
                        'default_risk_per_strategy_pct' => $model->default_risk_per_strategy_pct,
                    ],
                ];
                $stateJson = json_encode($state, JSON_PRETTY_PRINT);

                $systemPrompt = <<<TXT
You are a pre-market strategist for a leveraged equity/crypto account.

Your job is to build a DAILY TRADING PLAYBOOK for the upcoming session only.

Hard rules:
- You are planning for the model with id={$model->id} and name="{$model->name}".
- Use the risk and configuration fields from the JSON state when sizing strategies.
- Do NOT open trades or manage positions yourself, only propose a plan.
- Output MUST be STRICT JSON (no markdown, no commentary outside JSON).

The user state JSON will include:
- current equity
- any open positions (if they exist)
- planning and risk settings
TXT;

                $userPrompt = <<<TXT
Here is the current planning state as JSON:
{$stateJson}

Using this information, and the planning instructions below, build a DAILY STRATEGY PLAN for today:

{$model->premarket_prompt}

Your response must be a JSON ARRAY of strategy objects. Each object MUST have at least:

- "symbol": string (e.g. "TSLA")
- "direction": "long" | "short"
- "type": string description (e.g. "trend_follow", "mean_reversion", "distribution_short", "carry_trade")
- "mode": "active_on_open" | "sleeper"
- "entry_zone": string describing price range or condition (e.g. "400-405" or "above 415 on strong momentum")
- "stop_loss": number (approx stop level)
- "take_profit": number (approx target level)
- "invalid_level": number (where the thesis is clearly wrong)
- "max_size_usd": number (maximum notional allocation for this strategy)
- "priority": integer (1 = highest)
- "notes": short string (<= 3 sentences explaining the idea)

Respect, as soft caps:
- max_strategies_per_day
- max_symbols_per_day

Do NOT include any other top-level fields. Return ONLY the JSON array.
TXT;

                $apiKey = config('services.openai.key');
                if (!$apiKey) {
                    throw new \RuntimeException('Missing OpenAI API key in services.openai.key');
                }

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ])->post('https://api.openai.com/v1/responses', [
                    'model'  => config('services.openai.model', 'gpt-4.1-mini'),
                    'input'  => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $userPrompt],
                    ],
                    'max_output_tokens' => 2048,
                ]);

                if (!$response->successful()) {
                    throw new \RuntimeException('OpenAI API error: ' . $response->body());
                }

                $body = $response->json();
                $outputText = $body['output'][0]['content'][0]['text'] ?? null;
                if (!$outputText) {
                    throw new \RuntimeException('No output text from pre-market model.');
                }

                $plan = json_decode($outputText, true);
                if (!is_array($plan)) {
                    throw new \RuntimeException('Pre-market model did not return valid JSON array.');
                }

                $dailyPlan = AiDailyPlan::updateOrCreate(
                    [
                        'ai_model_id' => $model->id,
                        'trade_date'  => $tradeDate,
                    ],
                    [
                        'plan_json' => $plan,
                    ]
                );

                ModelLog::create([
                    'ai_model_id' => $model->id,
                    'action'      => 'PREMARKET_PLAN',
                    'payload'     => [
                        'date'     => $tradeDate,
                        'state'    => $state,
                        'plan'     => $plan,
                        'raw_text' => $outputText,
                    ],
                ]);

                $this->info("Stored pre-market plan id={$dailyPlan->id} for model {$model->id} on {$tradeDate}.");
            } catch (\Throwable $e) {
                $this->error("Error in pre-market for model {$model->id}: " . $e->getMessage());
                ModelLog::create([
                    'ai_model_id' => $model->id,
                    'action'      => 'PREMARKET_ERROR',
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

<?php

namespace App\Console\Commands;

use App\Models\AiDailyPlan;
use App\Models\AiModel;
use App\Models\ModelLog;
use App\Models\Position;
use App\Services\MarketData;
use Illuminate\Console\Command;

class SimulateTrades extends Command
{
    protected $signature = 'simulate:trades {--model_id=}';
    protected $description = 'Open paper positions from approved daily plan items';

    public function handle(): int
    {
        $query = AiModel::query()->where('active', true);

        if ($this->option('model_id')) {
            $query->where('id', $this->option('model_id'));
        }

        $models = $query->get();

        if ($models->isEmpty()) {
            $this->info('No active models found.');
            return self::SUCCESS;
        }

        /** @var MarketData $marketData */
        $marketData = app(MarketData::class);

        foreach ($models as $model) {
            $this->runForModel($model, $marketData);
        }

        return self::SUCCESS;
    }

    protected function runForModel(AiModel $model, MarketData $marketData): void
    {
        $planModel = AiDailyPlan::query()
            ->where('ai_model_id', $model->id)
            ->latest('trade_date')
            ->first();

        if (!$planModel || !is_array($planModel->plan_json) || empty($planModel->plan_json)) {
            $this->info("{$model->name}: no daily plan found.");
            return;
        }

        $approvedItems = collect($planModel->plan_json)
            ->filter(fn ($item) => is_array($item))
            ->filter(function ($item) {
                return (!empty($item['approved']) && $item['approved'])
                    || (($item['status'] ?? null) === 'approved')
                    || (!empty($item['keep']));
            })
            ->values();

        if ($approvedItems->isEmpty()) {
            $this->info("{$model->name}: no approved plan items.");
            return;
        }

        $openSymbols = Position::query()
            ->where('ai_model_id', $model->id)
            ->where('status', 'open')
            ->pluck('ticker')
            ->map(fn ($s) => strtoupper($s))
            ->all();

        $maxConcurrent = (int) ($model->max_concurrent_trades ?? 1);
        $openCount = count($openSymbols);
        $opened = 0;
        $skipped = 0;

        foreach ($approvedItems as $item) {
            if (($openCount + $opened) >= $maxConcurrent) {
                break;
            }

            $symbol = strtoupper($item['symbol'] ?? $item['ticker'] ?? '');
            if ($symbol === '') {
                $skipped++;
                continue;
            }

            if (in_array($symbol, $openSymbols, true)) {
                $skipped++;
                continue;
            }

            $price = (float) ($marketData->getPrice($symbol) ?? 0);
            if ($price <= 0) {
                $this->logSkip($model->id, $symbol, 'No current price');
                $skipped++;
                continue;
            }

            if (!$this->isEntryValid($item, $price)) {
                $this->logSkip($model->id, $symbol, 'Price not in entry zone', [
                    'last' => $price,
                    'entry_zone_low' => $item['entry_zone_low'] ?? null,
                    'entry_zone_high' => $item['entry_zone_high'] ?? null,
                ]);
                $skipped++;
                continue;
            }

            $direction = strtolower((string) ($item['direction'] ?? 'long'));
            $side = $direction === 'short' ? 'SHORT' : 'LONG';

            $equity = (float) ($model->equity ?? 0);
            $allocPct = (float) ($model->per_trade_alloc_pct ?? 0);
            $budget = $equity > 0 && $allocPct > 0
                ? ($equity * ($allocPct / 100))
                : 0;

            $qty = $price > 0 && $budget > 0
                ? max(1, (int) floor($budget / $price))
                : 1;

            $stop = $this->extractStop($item);
            $target = $this->extractTarget($item);

            Position::create([
                'ai_model_id' => $model->id,
                'ticker' => $symbol,
                'side' => $side,
                'qty' => $qty,
                'avg_price' => $price,
                'stop_price' => $stop,
                'target_price' => $target,
                'leverage' => 1,
                'margin' => $qty * $price,
                'unrealized_pnl' => 0,
                'status' => 'open',
                'opened_at' => now(),
            ]);

            ModelLog::create([
                'ai_model_id' => $model->id,
                'action' => 'OPEN',
                'summary' => "simulate:trades opened {$symbol}",
                'payload' => [
                    'source' => 'simulate:trades',
                    'symbol' => $symbol,
                    'side' => $side,
                    'qty' => $qty,
                    'entry_price' => $price,
                    'stop_price' => $stop,
                    'target_price' => $target,
                    'plan_item' => $item,
                ],
            ]);

            $opened++;
            $openSymbols[] = $symbol;
        }

        $this->info("{$model->name}: opened {$opened}, skipped {$skipped}.");
    }

    protected function isEntryValid(array $item, float $last): bool
    {
        $low = isset($item['entry_zone_low']) ? (float) $item['entry_zone_low'] : null;
        $high = isset($item['entry_zone_high']) ? (float) $item['entry_zone_high'] : null;
        $direction = strtolower((string) ($item['direction'] ?? 'long'));

        if ($low !== null && $high !== null) {
            return $last >= $low && $last <= $high;
        }

        if ($low !== null && $high === null) {
            return $direction === 'short'
                ? $last <= $low
                : $last >= $low;
        }

        return false;
    }

    protected function extractStop(array $item): ?float
    {
        $stop = $item['stop_loss'] ?? $item['invalid_level'] ?? null;
        return $stop !== null ? (float) $stop : null;
    }

    protected function extractTarget(array $item): ?float
    {
        $target = $item['take_profit'] ?? $item['target_1'] ?? null;
        return $target !== null ? (float) $target : null;
    }

    protected function logSkip(int $modelId, string $symbol, string $reason, array $extra = []): void
    {
        ModelLog::create([
            'ai_model_id' => $modelId,
            'action' => 'SKIP',
            'summary' => "simulate:trades skipped {$symbol}",
            'payload' => array_merge([
                'source' => 'simulate:trades',
                'symbol' => $symbol,
                'reason' => $reason,
            ], $extra),
        ]);
    }
}

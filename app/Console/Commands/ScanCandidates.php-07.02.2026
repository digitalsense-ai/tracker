<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\AiModel;
use App\Models\AiDailyCandidate;
use App\Models\ModelLog;
use App\Services\Scanner\DailyCandidateScanner;

class ScanCandidates extends Command
{
    protected $signature = 'scanner:run {--model_id=} {--date=} {--limit=}';
    protected $description = 'Build the daily candidate symbol list (NO AI) and store it in DB.';

    public function handle(): int
    {
        $tradeDate = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::today()->toDateString();

        $query = AiModel::query()->where('active', true);
        if ($this->option('model_id')) {
            $query->where('id', (int) $this->option('model_id'));
        }

        $models = $query->get();
        if ($models->isEmpty()) {
            $this->info('No active AI models found.');
            return self::SUCCESS;
        }

        foreach ($models as $model) {
            $limit = (int)($this->option('limit') ?? config('trading.scanner.candidate_limit', 25));
            $scanner = app(DailyCandidateScanner::class);
            [$symbols, $meta] = $scanner->scan($limit);

            AiDailyCandidate::updateOrCreate(
                ['ai_model_id' => $model->id, 'trade_date' => $tradeDate],
                ['symbols_json' => $symbols, 'meta_json' => $meta]
            );

            ModelLog::create([
                'ai_model_id' => $model->id,
                'action'      => 'SCANNER_CANDIDATES',
                'payload'     => [
                    'trade_date' => $tradeDate,
                    'limit' => $limit,
                    'symbols' => $symbols,
                    'meta' => $meta,
                ],
            ]);

            $this->info("Model {$model->id} candidates for {$tradeDate}: " . implode(', ', $symbols));
        }

        return self::SUCCESS;
    }
}

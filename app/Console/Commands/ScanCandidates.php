<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\AiModel;
use App\Models\AiDailyCandidate;
use App\Models\AiDailyCandidateItem;
use App\Models\ModelLog;
use App\Models\SaxoInstrument;
use App\Services\Scanner\DailyCandidateScanner;

class ScanCandidates extends Command
{
    protected $signature = 'scanner:run {--model_id=} {--date=} {--limit=}';
    protected $description = 'Build the daily candidate symbol list (NO AI) and store it in DB.';

    public function handle(): int
    {
        logger()->channel('scanner')->info('scanner.start');

        $marketData = app(\App\Services\MarketData::class);

        $allowSymbols = collect(
            array_filter(array_map('trim',
                explode(',', config('trading.scanner.allow_symbols'))
            ))
        )->map(fn ($s) => strtoupper($s));

        $denySymbols = collect(
            array_filter(array_map('trim',
                explode(',', config('trading.scanner.deny_symbols'))
            ))
        )->map(fn ($s) => strtoupper($s));

        $tradeDate = $this->option('date')
            ? Carbon::parse($this->option('date'))->toDateString()
            : Carbon::today()->toDateString();

        $modelId = $this->option('model_id');
        if (!$modelId) {
            $this->error('Missing required --model_id option');
            return self::FAILURE;
        }

        $query = AiModel::query()->where('active', true);
        if ($modelId) {
            $query->where('id', (int) $modelId);
        }  

        $model = $query->first();        
        if (!$model) {
            $this->info('No active AI models found.');
            return self::SUCCESS;
        }
        
        /*
        |--------------------------------------------------------------------------
        | STEP 0 — Guard clause (freeze guarantee)
        |--------------------------------------------------------------------------
        */
        $alreadyFrozen = AiDailyCandidate::where('trade_date', $tradeDate)
            ->where('ai_model_id', $modelId)
            ->whereJsonLength('symbols_json', '>', 0)
            ->exists();

        if ($alreadyFrozen) {
            logger()->channel('scanner')->info('scanner.freeze.complete', [
                'trade_date' => $tradeDate,
                'ai_model_id' => $modelId,
            ]);

            $this->info("Scanner frozen — candidates already exist for {$tradeDate}");
            return self::SUCCESS;
        }


        // if (AiDailyCandidate::where('trade_date', $tradeDate)->exists()) {
        //     logger()->info('scanner.freeze.complete', ['trade_date' => $tradeDate]);
        //     $this->info("Scanner frozen — candidates already exist for {$tradeDate}");
        //     return self::SUCCESS;
        // }

        $weights = config('trading.scanner.weights');

        /*
        |--------------------------------------------------------------------------
        | STEP 1 — Load universe (broker truth)
        |--------------------------------------------------------------------------
        */
        $universe = SaxoInstrument::query()
            ->where('is_tradable', 1)
            ->whereIn('asset_type', ['Stock'])
            ->whereIn('exchange_id', ['NASDAQ', 'NYSE'])
            ->get();

        $this->info("Universe size: {$universe->count()}");

        /*
        |--------------------------------------------------------------------------
        | STEP 2 — Topic 1 filters (NO ranking)
        |--------------------------------------------------------------------------
        */
        $filtered = collect();

        foreach ($universe as $instrument) {

            // Original symbol
            $rawSymbol = $instrument->symbol; // e.g. "NVDA:xnas"
            // Take only the part before the colon
            $symbol = strtoupper(strtok($rawSymbol, ':')); // -> "NVDA"

            if (!$symbol) {
                $this->reject('no_symbol', $instrument);
                continue;
            } 
                       
            if ($allowSymbols->isNotEmpty() && !$allowSymbols->contains($symbol)) {
                $this->reject('not_in_allowlist', $instrument);
                continue;
            }

            if ($denySymbols->contains($symbol)) {
                $this->reject('denylist', $instrument);
                continue;
            }

            $price = $marketData->getPrice($symbol);

            if (config('trading.scanner.require_price') && ($price === null || $price <= 0)) {
                $this->reject('no_price', $instrument);
                continue;
            }
            
            if ($price !== null) {
               $min = config('trading.scanner.min_price');
               $max = config('trading.scanner.max_price');
               if ($min !== null && $price < $min) { $this->reject('min_price', $instrument); continue; }
               if ($max !== null && $price > $max) { $this->reject('max_price', $instrument); continue; }
            }

            $tradableAs = is_array($instrument->tradable_as)
                            ? $instrument->tradable_as
                            : json_decode($instrument->tradable_as, true) ?? [];

            $disallowed = ['Etf', 'Fund', 'Etn', 'Etp'];

            if (array_intersect($tradableAs, $disallowed)) {
                $this->reject('fund_like', $instrument);
                continue;
            }

            if (!in_array('Stock', $tradableAs, true)) {
                $this->reject('not_stock', $instrument);
                continue;
            }
            
            $mapping = \App\Models\SymbolMapping::where('symbol', $symbol)->first();

            if (!$mapping) {
                $this->reject('no_mapping', $instrument);
                continue;
            }

            if (!$mapping->enabled_for_ai) {
                $this->reject('disabled', $instrument);
                continue;
            }

            $priority = $mapping->priority ?? 999;

            $filtered->push((object) [
                'symbol'     => $symbol,
                'uic'        => $instrument->uic,
                'asset_type' => $instrument->asset_type,
                'price'      => $price !== null ? (float)$price : null,
                'priority'   => $priority,
                'instrument' => $instrument,
            ]);
        }

        $this->info("After filters: {$filtered->count()}");
        logger()->channel('scanner')->info('scanner.filtered.count', ['count' => $filtered->count()]);

        /*
        |--------------------------------------------------------------------------
        | STEP 3 — Pre-rank thinning (quota safety)
        |--------------------------------------------------------------------------
        */
        $filtered = $filtered
            ->sortByDesc('price')
            ->take(100)
            ->values();

        /*
        |--------------------------------------------------------------------------
        | STEP 4 — Fetch daily bars (Saxo)
        |--------------------------------------------------------------------------
        */
        $chartService = app(\App\Services\SaxoChartService::class);
        $scored = collect();

        foreach ($filtered as $candidate) {

            $bars = $chartService->getDailyBars(
                uic: $candidate->uic,
                assetType: $candidate->asset_type,
                count: 30
            );

            usort($bars, fn($a,$b) => strcmp($a['date'], $b['date']));

            if (count($bars) < 21) {
                $this->reject('insufficient_bars', $candidate->instrument);
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | STEP 5 — Metrics
            |--------------------------------------------------------------------------
            */

            // --- ATR proxy (14)
            $trs = [];

            for ($i = 1; $i < count($bars); $i++) {
                $prev = $bars[$i - 1];
                $cur  = $bars[$i];

                $trs[] = max(
                    $cur['high'] - $cur['low'],
                    abs($cur['high'] - $prev['close']),
                    abs($cur['low'] - $prev['close'])
                );
            }

            //$atr14   = collect($trs)->take(14)->avg();
            $atr14 = collect($trs)->slice(-14)->avg();
            $last    = last($bars)['close'];
            $atrPct  = $atr14 / $last;

            // --- Momentum (20)
            $close20 = $bars[count($bars) - 21]['close'];
            $momentum = abs($last / $close20 - 1);

            // --- Range expansion
            $todayRangePct = (last($bars)['high'] - last($bars)['low']) / $last;

            $ranges = collect($bars)
                //->take(20)
                ->slice(-20)
                ->map(fn ($b) => ($b['high'] - $b['low']) / $b['close']);

            $median = $ranges->median();

            if ($median == 0) {
                $this->reject('zero_median_range', $candidate->instrument);
                continue;
            }

            $rangeScore = $todayRangePct / $median;

            $candidate->metrics = [
                'atr14'        => $atr14,
                'atr_pct'      => $atrPct,
                'momentum_20'  => $momentum,
                'range_score'  => $rangeScore,
                'median_range' => $median,
            ];

            /*
            |--------------------------------------------------------------------------
            | STEP 6 — Final score (deterministic)
            |--------------------------------------------------------------------------
            */        
            $score =
                $weights['atr']   * $atrPct +
                $weights['mom']   * $momentum +
                $weights['range'] * $rangeScore;            

            $candidate->score = $score;
            $scored->push($candidate);
        }

        /*
        |--------------------------------------------------------------------------
        | STEP 7 — Rank & freeze
        |--------------------------------------------------------------------------
        */
        logger()->channel('scanner')->info('scanner.rank.start');
        $ranked = $scored
            ->sortBy([
                ['priority', 'asc'],
                ['score', 'desc'],
                ['symbol', 'asc'],
            ])
            ->take(config('trading.scanner.candidate_limit'))
            ->values();
        logger()->channel('scanner')->info('scanner.rank.complete', ['final_count' => $ranked->count()]);

        foreach ($ranked as $i => $c) {
            AiDailyCandidateItem::updateOrCreate(
                [
                    'ai_model_id' => $modelId, 
                    'trade_date' => $tradeDate,
                    'symbol'     => $c->symbol
                ],
                [
                    'ai_model_id' => $modelId,
                    'trade_date' => $tradeDate,
                    'symbol'     => $c->symbol,
                    'rank'       => $i + 1,
                    'price'      => $c->price,
                    'score'      => $c->score,
                    'saxo_uic'        => $c->uic,
                    'saxo_asset_type' => $c->asset_type,
                    'metrics_json'=> $c->metrics,
                    'source'     => 'scanner_v4',
                ]
            );            
        }

        // Step 2 — write compact AI-facing list
        //$symbols = $ranked->sortBy('rank')->pluck('symbol');
        $symbols = $ranked->pluck('symbol')->values();

        AiDailyCandidate::updateOrCreate(
            ['ai_model_id' => $modelId, 'trade_date' => $tradeDate],
            [
                'symbols_json' => $symbols,
                'meta_json' => [
                    'scanner_version' => 'v4',
                    'ranking_mode'    => 'deterministic',
                    'candidate_count' => $symbols->count(),
                    'generated_at'    => now()->toDateTimeString(),
                ],
            ]
        );

        ModelLog::create([
            'ai_model_id' => $modelId,
            'action' => 'SCANNER_CANDIDATES',
            'payload' => [
                'trade_date' => $tradeDate,
                'source'     => 'scanner_v4',
                'config'     => [
                    'min_price' => config('trading.scanner.min_price'),
                    'max_price' => config('trading.scanner.max_price'),
                    'weights'   => $weights,
                ],
                'final_count' => $ranked->count(),
            ],
        ]);

        $this->info("Scanner complete — {$ranked->count()} candidates frozen.");

        return self::SUCCESS;
    }

    protected function reject(string $reason, SaxoInstrument $instrument): void
    {
        // Original symbol
        $rawSymbol = $instrument->symbol; // e.g. "NVDA:xnas"
        // Take only the part before the colon
        $symbol = strtoupper(strtok($rawSymbol, ':')); // -> "NVDA"

        logger()->channel('scanner')->info("scanner.reject.{$reason}", [
            'symbol' => $symbol,
            'uic'    => $instrument->uic,
        ]);
    }

    /*
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
    */
}

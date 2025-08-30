<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BacktestService;
use Carbon\Carbon;
use Throwable;

class SimulateV5 extends Command
{
    protected $signature = 'backtest:simulate-v5 
                            {--date= : Startdato i format YYYY-MM-DD (lokal tid)} 
                            {--days=1 : Antal kalenderdage} 
                            {--opts=* : Ekstra options som key=value, fx instrument=DE40 session=08:30-10:00 fees_bps=1.5}';

    protected $description = 'Kør V5 backtest-simulation for en specifik dato/interval (adapter for forskellige simulate()-signaturer)';

    public function __construct(protected BacktestService $backtestService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $days = (int) $this->option('days') ?: 1;

            $dateOpt = $this->option('date');
            $startDate = $dateOpt 
                ? Carbon::createFromFormat('Y-m-d', $dateOpt)->startOfDay()
                : Carbon::now('Europe/Copenhagen')->startOfDay();

            // Parse --opts key=value
            $rawOpts = (array) $this->option('opts');
            $options = [];
            foreach ($rawOpts as $pair) {
                if (str_contains($pair, '=')) {
                    [$k, $v] = explode('=', $pair, 2);
                    $options[trim($k)] = trim($v);
                }
            }

            $this->info(sprintf(
                'Kører simulateForDate() fra %s i %d dag(e) ...',
                $startDate->toDateString(),
                $days
            ));

            $results = $this->backtestService->simulateForDate($startDate, $days, $options);

            if (is_array($results) || $results instanceof \JsonSerializable) {
                $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->line('OK');
            }

            $this->info('Færdig ✅');
            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error('Fejl: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}

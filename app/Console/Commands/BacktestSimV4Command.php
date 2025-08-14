<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StrategyV4Service;
use App\Models\Stock;
use Carbon\Carbon;

class BacktestSimV4Command extends Command
{
    protected $signature = 'backtest:simulate-v4 {--date=}';
    protected $description = 'Run V4 backtest simulation';

    public function handle(StrategyV4Service $service)
    {
        $date = $this->option('date') ?? Carbon::now()->subDay()->format('Y-m-d');
        $tickers = Stock::pluck('ticker')->toArray();
        $results = $service->runBacktest($tickers, $date);

        $this->info("Backtest V4 completed for " . count($results) . " trades.");
    }
}

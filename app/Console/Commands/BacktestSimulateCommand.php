<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BacktestService;

class BacktestSimulateCommand extends Command
{
    protected $signature = 'backtest:simulate';
    protected $description = 'Simulate backtest trades with TP/SL logic';

    public function handle(BacktestService $backtestService)
    {
        $this->info('Running backtest simulation...');
        $backtestService->run();
        $this->info('Backtest simulation completed.');
    }
}

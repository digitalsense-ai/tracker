<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BacktestService;

class BacktestSimulateCommand extends Command
{
    protected $signature = 'backtest:simulate';
    protected $description = 'Simulate trades using the ORB Retest strategy';

    public function handle(BacktestService $backtestService)
    {
        $results = $backtestService->simulate();

        if (empty($results)) {
            $this->warn('No simulated trades generated.');
        } else {
            foreach ($results as $trade) {
                $this->info("{$trade['ticker']} - Entry: {$trade['entryPrice']} TP1: {$trade['tp1']} TP2: {$trade['tp2']} SL: {$trade['slPrice']} => {$trade['status']}");
            }
        }
    }
}

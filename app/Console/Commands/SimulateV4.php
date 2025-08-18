<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BacktestService;
use Carbon\Carbon;

class SimulateV4 extends Command
{
    protected $signature = 'backtest:simulate-v4 
        {--days=0 : Number of past days to run} 
        {--from= : Start date YYYY-MM-DD} 
        {--to= : End date YYYY-MM-DD} 
        {--date= : Run for a single date (YYYY-MM-DD)}';

    protected $description = 'Run V4 backtest for single date, multiple days, or date range';

    public function handle(BacktestService $service)
    {
        $days   = (int) $this->option('days');
        $from   = $this->option('from');
        $to     = $this->option('to');
        $date   = $this->option('date');

        if ($date) {
            $this->info("Running V4 backtest for {$date}");
            $result = $service->simulateForDate(Carbon::parse($date)->format('Y-m-d'));
            $this->info("Trades: " . count($result));
            return self::SUCCESS;
        }

        if ($from && $to) {
            $this->info("Running V4 backtest from {$from} to {$to}");
            $period = new \DatePeriod(
                Carbon::parse($from),
                new \DateInterval('P1D'),
                Carbon::parse($to)->addDay()
            );
            $total = 0;
            foreach ($period as $d) {
                $ds = $d->format('Y-m-d');
                $this->line(" - {$ds}");
                $total += count($service->simulateForDate($ds));
            }
            $this->info("Total trades: {$total}");
            return self::SUCCESS;
        }

        if ($days > 0) {
            $this->info("Running V4 backtest for last {$days} day(s)");
            $result = $service->simulate($days);
            $this->info("Total trades: " . count($result));
            return self::SUCCESS;
        }

        $y = Carbon::yesterday()->format('Y-m-d');
        $this->info("Running V4 backtest for {$y}");
        $result = $service->simulateForDate($y);
        $this->info("Trades: " . count($result));
        return self::SUCCESS;
    }
}

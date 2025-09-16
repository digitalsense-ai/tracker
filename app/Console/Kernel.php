<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SimulateTrades::class,
        \App\Console\Commands\BacktestGridCommand::class,
        \App\Console\Commands\SimulateV5::class,
        \App\Console\Commands\ProfilesRecompute::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Example: run a command every day
        // $schedule->command('inspire')->daily();

        $schedule->command('forecast:refresh')->everyFiveMinutes();
        $schedule->command('simulate:trades')->dailyAt('15:40'); // fx 15:40 dansk tid
        $schedule->command('profiles:recompute')->weekdays()->at('17:15');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
protected $commands = [
    \App\Console\Commands\BacktestSimulateCommand::class,
];

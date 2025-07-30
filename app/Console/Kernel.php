<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SimulateTrades::class,
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

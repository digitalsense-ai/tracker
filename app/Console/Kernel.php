<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Storage;

use App\Models\AiModel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\SimulateTrades::class,
        \App\Console\Commands\BacktestGridCommand::class,
        \App\Console\Commands\SimulateV5::class,
        \App\Console\Commands\ProfilesRecompute::class,
        \App\Console\Commands\BacktestSimulateCommand::class,
        \App\Console\Commands\AiTick::class,
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
        //$schedule->command('profiles:recompute')->weekdays()->at('17:15');
        //$schedule->command('profiles:recompute')->cron('3 * * * *');

        // $schedule->command('run:nyopen --yesterday')
        //      ->weekdays()
        //      ->at('23:30')
        //      ->appendOutputTo(storage_path('logs/nyopen.log'));

        // $schedule->command('profiles:recompute --days=0 --table=trades --ts=created_at --auto-pnl')
        //      ->weekdays()
        //      ->at('23:30')
        //      ->appendOutputTo(storage_path('logs/nyopen.log'));

        $schedule->command('nyopen:backtest --date=yesterday')
             ->weekdays()
             ->at('23:30')
             ->appendOutputTo(storage_path('logs/nyopen.log'));

        $schedule->command('profiles:recompute --days=0 --table=trades --ts=created_at --auto-pnl')
             ->weekdays()
             ->at('23:30')
             ->appendOutputTo(storage_path('logs/nyopen.log'));

        $schedule->command('ai:tick')->everyMinute();

        // Daily at 9 AM
        $schedule->call(function () {
            // Get all models to process
            //$models = DB::table('models')->where('active', true)->pluck('id'); // replace 'models' with your table
            $models = AiModel::where('active', true)->pluck('id');

            foreach ($models as $modelId) {
                // Run the command for each model
                \Artisan::call('ai:premarket', [
                    '--model_id' => $modelId,
                    '--date' => now()->format('Y-m-d'),
                ]);

                // Run the command for v2
                \Artisan::call('ai:premarket-v2', [
                    '--model_id' => $modelId,                  
                ]);
            }
        })
        ->name('ai-premarket-daily')   // required for withoutOverlapping
        ->withoutOverlapping()          // prevents multiple runs at the same time
        //->runInBackground()             // runs the task in the background
        ->dailyAt('00:00');                      // schedules it to run daily
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
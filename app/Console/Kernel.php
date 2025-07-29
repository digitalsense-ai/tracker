protected function schedule(Schedule $schedule)
{
    $schedule->command('forecast:refresh')->everyFiveMinutes();
}
protected $commands = [
    \App\Console\Commands\SimulateTrades::class,
];

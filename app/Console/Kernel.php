protected function schedule(Schedule $schedule)
{
    $schedule->command('forecast:refresh')->everyFiveMinutes();
}
protected $commands = [
    \App\Console\Commands\SimulateTrades::class,
];
protected function schedule(Schedule $schedule)
{
    $schedule->command('simulate:trades')->dailyAt('15:40'); // fx 15:40 dansk tid
}

protected function schedule(Schedule $schedule)
{
    $schedule->command('forecast:refresh')->everyFiveMinutes();
}

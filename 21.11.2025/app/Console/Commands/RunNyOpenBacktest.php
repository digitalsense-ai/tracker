<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Carbon\CarbonImmutable;
use App\Jobs\RunNyOpenBacktestJob;

class RunNyOpenBacktest extends Command
{
    protected $signature = 'nyopen:backtest
        {--date= : Dato i YYYY-MM-DD eller "yesterday" (default) }
        {--universe= : Komma-separeret tickers (ellers brug config) }
        {--profiles= : Komma-separeret profilnavne (P001,P002,…) (ellers alle enabled) }
        {--dry-run : Kør uden at skrive trades og uden recompute }';

    protected $description = 'Kør backtest for NYSE åbning (09:30–11:30 ET) for valgt dato og univers.';

    public function handle()
    {
        $cfg = config('nyopen');

        // Resolve date
        $dateOpt = $this->option('date') ?: 'yesterday';
        if ($dateOpt === 'yesterday') {
            $date = CarbonImmutable::now($cfg['tz_app'])->subDay()->format('Y-m-d');
        } else {
            $date = CarbonImmutable::parse($dateOpt, $cfg['tz_app'])->format('Y-m-d');
        }

        // Universe
        $universe = $this->option('universe')
            ? array_filter(array_map('trim', explode(',', $this->option('universe'))))
            : $cfg['default_universe'];

        // Profiles
        $profiles = $this->option('profiles')
            ? array_filter(array_map('trim', explode(',', $this->option('profiles'))))
            : []; // fetch in job if empty

        $dry = (bool)$this->option('dry-run');

        $this->info('--- NYOPEN BACKTEST ---');
        $this->line('Date      : ' . $date);
        $this->line('Universe  : [' . implode(',', $universe) . ']');
        $this->line('Profiles  : ' . (empty($profiles) ? '(ALL enabled)' : '[' . implode(',', $profiles) . ']'));
        $this->line('Dry-run   : ' . ($dry ? 'YES' : 'NO'));

        RunNyOpenBacktestJob::dispatch($date, $universe, $profiles, $dry);

        if ($dry) {
            $this->warn('Dry-run: recompute skipped.');
            return Command::SUCCESS;
        }

        // Recompute after job (adjust to your queue setup; you may want to move this to a listener)
        $this->line('Recomputing profile results...');
        Artisan::call('profiles:recompute', [
            '--days' => 0,
            '--table' => 'trades',
            '--ts' => 'created_at',
            '--auto-pnl' => true,
            '-v' => true,
        ]);
        $this->line(Artisan::output());

        $this->info('Done.');
        return Command::SUCCESS;
    }
}

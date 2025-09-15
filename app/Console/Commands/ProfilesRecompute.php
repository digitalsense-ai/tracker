<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProfilesRecompute extends Command
{
    protected $signature = 'profiles:recompute
        {--days=10 : Window size in days}
        {--limit=128 : Max number of profiles to compute}
        {--skip-sim : Skip the simulation step and only aggregate}
        {--dry-run : Do not write to DB}
        {--vv : Verbose output}';

    protected $description = 'Run simulation and recompute profile_results for the last N days';

    public function handle(): int
    {
        $days  = (int) $this->option('days');
        $limit = (int) $this->option('limit');
        $skipSim = (bool) $this->option('skip-sim');
        $dryRun  = (bool) $this->option('dry-run');
        $vv      = (bool) $this->option('vv');

        if ($days < 1) {
            $this->error('Option --days must be >= 1');
            return self::FAILURE;
        }

        $end   = Carbon::today();
        $start = $end->copy()->subDays($days - 1);
        $windowLabel = $start->toDateString() . '..' . $end->toDateString();

        $this->info(sprintf('--- PROFILES RECOMPUTE ---'));
        $this->line(sprintf('Window: %s (%d days)', $windowLabel, $days));

        if (!$skipSim) {
            $this->simulateTrades($days, $vv);
        }

        $profiles = DB::table('strategy_profiles')
            ->where('enabled', 1)
            ->orderBy('id')
            ->limit($limit)
            ->get(['id', 'name', 'rules', 'enabled']);

        $trades = DB::table('simulated_trades')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $now = Carbon::now();
        $rowsToInsert = [];

        foreach ($profiles as $p) {
            $pTrades = $trades; // TODO: filtrer efter profilregler
            $tradesCount = $pTrades->count();
            $netPl = (float)$pTrades->sum(fn($t) => (float)$t->net_profit);
            $wins = $pTrades->filter(fn($t) => (float)$t->net_profit > 0)->count();
            $winrate = $tradesCount > 0 ? round(100.0 * $wins / $tradesCount, 2) : 0.0;

            $rowsToInsert[] = [
                'strategy_profile_id' => $p->id,
                'window' => $windowLabel,
                'trades' => $tradesCount,
                'winrate'=> $winrate,
                'avg_r'  => 0.0,
                'net_pl' => $netPl,
                'profit_factor' => 0.0,
                'drawdown_pct' => 0.0,
                'score' => 0.0,
                'metrics' => json_encode(['net_pl'=>$netPl,'trades'=>$tradesCount,'winrate'=>$winrate]),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!$dryRun && count($rowsToInsert) > 0) {
            DB::table('profile_results')->where('window', $windowLabel)->delete();
            DB::table('profile_results')->insert($rowsToInsert);
        }

        $this->info('Done. Inserted rows: '.count($rowsToInsert));
        return self::SUCCESS;
    }

    protected function simulateTrades(int $days, bool $vv): void
    {
        try {
            Artisan::call('backtest:simulate-v5', ['--days' => $days]);
        } catch (\Throwable $e) {
            try {
                Artisan::call('backtest:simulate', ['--days' => $days]);
            } catch (\Throwable $e) {
                $this->warn('No simulation command available.');
            }
        }
    }
}

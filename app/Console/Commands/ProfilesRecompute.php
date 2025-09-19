<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProfilesRecompute extends Command
{
    protected $signature = 'profiles:recompute
        {--days=90}
        {--table=trades}
        {--ts=created_at}
        {--pnl=}
        {--auto-pnl}
        {--short}
        {--win=}';
    protected $description = 'Recompute profile_results per strategy_profile_id from trades (real attribution).';

    public function handle(): int
    {
        $days   = (int)$this->option('days');
        $table  = (string)$this->option('table');
        $ts     = (string)$this->option('ts');
        $pnlCol = $this->option('pnl') ?: null;
        $autoPnL= (bool)$this->option('auto-pnl');
        $short  = (bool)$this->option('short');
        $winCol = $this->option('win') ?: null;

        if (!Schema::hasTable($table)) { $this->error("Table {$table} not found"); return self::FAILURE; }
        foreach ([$ts,'strategy_profile_id'] as $c) {
            if (!Schema::hasColumn($table,$c)) { $this->error("Missing {$table}.{$c}"); return self::FAILURE; }
        }

        $end   = now();
        $start = $days > 0 ? now()->subDays($days)->startOfDay() : null;

        $base = DB::table($table)->whereNotNull('strategy_profile_id');
        if ($start) $base->whereBetween($ts, [$start, $end]);

        $profileIds = (clone $base)->distinct()->pluck('strategy_profile_id')->filter()->values();

        $this->line(sprintf(
            "--- PROFILES RECOMPUTE (FK) ---%sWindow: %s..%s (%s)%sProfiles with trades: %d",
            PHP_EOL,
            $start?->toDateTimeString() ?? 'ALL', $end->toDateTimeString(),
            $days>0 ? "{$days} days" : 'no filter',
            PHP_EOL,
            $profileIds->count()
        ));

        foreach ($profileIds as $pid) {
            $q = (clone $base)->where('strategy_profile_id', $pid);

            $trades = (clone $q)->count();

            if ($pnlCol && Schema::hasColumn($table,$pnlCol)) {
                $pnl  = (clone $q)->sum($pnlCol);
                $wins = (clone $q)->where($pnlCol,'>',0)->count();
            } elseif ($autoPnL && Schema::hasColumn($table,'entry_price') && Schema::hasColumn($table,'exit_price')) {
                $rows = (clone $q)->select('entry_price','exit_price')->get();
                $pnl=0; $wins=0;
                foreach ($rows as $r) {
                    if ($r->entry_price!==null && $r->exit_price!==null) {
                        $p = $short ? ($r->entry_price - $r->exit_price) : ($r->exit_price - $r->entry_price);
                        $pnl += $p; if ($p>0) $wins++;
                    }
                }
            } else { $pnl=0; $wins=0; }

            $minTs = (clone $q)->min($ts);
            $maxTs = (clone $q)->max($ts);
            $winRate = $trades>0 ? round($wins/$trades*100,2) : 0;

            $payload = [
                'strategy_profile_id' => $pid,
                'trades' => $trades,
                'pnl' => $pnl,
                'win_rate' => $winRate,
                'window_start' => $minTs,
                'window_end'   => $maxTs,
                'updated_at'   => now(),
            ];
            $existing = DB::table('profile_results')->where('strategy_profile_id',$pid)->first();
            if ($existing) {
                DB::table('profile_results')->where('id',$existing->id)->update($payload);
            } else {
                $payload['created_at'] = now();
                DB::table('profile_results')->insert($payload);
            }

            $name = DB::table('strategy_profiles')->where('id',$pid)->value('name') ?? ('#'.$pid);
            $this->line(sprintf("%s trades=%d pnl=%.2f win%%=%.2f  %s..%s",
                $name, $trades, $pnl, $winRate, $minTs ?? '—', $maxTs ?? '—'
            ));
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}

<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProfilesRecompute extends Command
{
    protected $signature = 'profiles:recompute
        {--days=10 : Lookback window in days (0 = all)}
        {--limit=128 : Max profiles to process}
        {--table=trades : Trades table name}
        {--ts=created_at : Timestamp column on trades}
        {--pnl= : Numeric PnL column on trades (optional)}
        {--auto-pnl : If no PnL column, compute exit_price - entry_price}
        {--short : With --auto-pnl, compute entry_price - exit_price}
        {--win= : Boolean win column on trades (optional)}
        {--dry-run : Print only, do not write results}';
    protected $description = 'Recompute profile_results using strategy_profiles.external_key mapped to trades.forecast_type';

    public function handle(): int
    {
        $days    = (int)$this->option('days');
        $limit   = (int)$this->option('limit');
        $table   = (string)$this->option('table');
        $ts      = (string)$this->option('ts');
        $pnlCol  = $this->option('pnl') ?: null;
        $autoPnL = (bool)$this->option('auto-pnl');
        $isShort = (bool)$this->option('short');
        $winCol  = $this->option('win') ?: null;
        $dryRun  = (bool)$this->option('dry-run');

        if (!Schema::hasTable($table)) {
            $this->error("Trades table '{$table}' not found.");
            return self::FAILURE;
        }
        if (!Schema::hasColumn($table, 'forecast_type')) {
            $this->error("Column trades.forecast_type not found.");
            return self::FAILURE;
        }
        if (!Schema::hasColumn($table, $ts)) {
            $this->error("Timestamp column '{$ts}' not found on {$table}.");
            return self::FAILURE;
        }

        $windowEnd   = now();
        $windowStart = $days > 0 ? now()->subDays($days)->startOfDay() : null;

        $profiles = DB::table('strategy_profiles')
            ->when(Schema::hasColumn('strategy_profiles','enabled'), fn($q) => $q->where('enabled', 1))
            ->orderBy('id')->limit($limit)
            ->get(['id','name','external_key']);

        $this->line(sprintf(
            "--- PROFILES RECOMPUTE ---%sWindow: %s..%s (%s)%sProfiles: %d",
            PHP_EOL,
            $windowStart?->toDateTimeString() ?? 'ALL',
            $windowEnd->toDateTimeString(),
            $days > 0 ? ($days.' days') : 'no date filter',
            PHP_EOL,
            $profiles->count()
        ));

        $written = 0;

        foreach ($profiles as $p) {
            if (!$p->external_key) {
                if ($this->getOutput()->isVerbose()) {
                    $this->line("Skip {$p->name} (no external_key)");
                }
                continue;
            }

            $base = DB::table($table)->where('forecast_type', $p->external_key);
            if ($windowStart) {
                $base->whereBetween($ts, [$windowStart, $windowEnd]);
            }

            $trades = (clone $base)->count();

            // PnL
            if ($pnlCol && Schema::hasColumn($table, $pnlCol)) {
                $pnl = (clone $base)->sum($pnlCol);
            } elseif ($autoPnL && Schema::hasColumn($table,'entry_price') && Schema::hasColumn($table,'exit_price')) {
                $pnl = 0;
                $rows = (clone $base)->select('entry_price','exit_price')->get();
                foreach ($rows as $r) {
                    if ($r->entry_price !== null && $r->exit_price !== null) {
                        $pnl += $isShort ? ($r->entry_price - $r->exit_price) : ($r->exit_price - $r->entry_price);
                    }
                }
            } else {
                $pnl = 0;
            }

            // Wins
            if ($winCol && Schema::hasColumn($table, $winCol)) {
                $wins = (clone $base)->where($winCol, 1)->count();
            } elseif ($pnlCol && Schema::hasColumn($table, $pnlCol)) {
                $wins = (clone $base)->where($pnlCol, '>', 0)->count();
            } elseif ($autoPnL && Schema::hasColumn($table,'entry_price') && Schema::hasColumn($table,'exit_price')) {
                $wins = 0;
                $rows = (clone $base)->select('entry_price','exit_price')->get();
                foreach ($rows as $r) {
                    if ($r->entry_price !== null && $r->exit_price !== null) {
                        $tmp = $isShort ? ($r->entry_price - $r->exit_price) : ($r->exit_price - $r->entry_price);
                        if ($tmp > 0) $wins++;
                    }
                }
            } else {
                $wins = 0;
            }

            $minTs = (clone $base)->min($ts);
            $maxTs = (clone $base)->max($ts);
            $winRate = $trades > 0 ? round(($wins / max($trades,1)) * 100, 2) : 0;

            if ($this->getOutput()->isVerbose()) {
                $this->line(sprintf("%s [%s] trades=%d pnl=%.2f win%%=%.2f  %s..%s",
                    $p->name, $p->external_key, $trades, $pnl, $winRate,
                    $minTs ?? '—', $maxTs ?? '—'
                ));
            }

            if ($dryRun) continue;

            $payload = [
                'strategy_profile_id' => $p->id,
                'trades'       => $trades,
                'pnl'          => $pnl,
                'win_rate'     => $winRate,
                'window_start' => $minTs,
                'window_end'   => $maxTs,
                'updated_at'   => now(),
            ];

            $existing = DB::table('profile_results')->where('strategy_profile_id', $p->id)->first();
            if ($existing) {
                DB::table('profile_results')->where('id', $existing->id)->update($payload);
            } else {
                $payload['created_at'] = now();
                DB::table('profile_results')->insert($payload);
            }
            $written++;
        }

        if ($dryRun) {
            $this->info('Dry-run complete. No changes written.');
        } else {
            $this->info("Done. Rows written/updated: {$written}.");
        }

        return self::SUCCESS;
    }
}

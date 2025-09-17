<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProfilesRecompute extends Command
{
    protected $signature = 'profiles:recompute
        {--days=10 : Lookback window in days (0 = all)}
        {--limit=128 : Max number of profiles}
        {--table= : Override trades table name (default from config)}
        {--fk= : Override FK column on trades (e.g. strategy_id)}
        {--map= : Join map profiles.<col>=trades.<col> (e.g. name=forecast_type)}
        {--ts= : Override timestamp column on trades (e.g. created_at)}
        {--pnl= : Override PnL column on trades (numeric)}
        {--auto-pnl : If no PnL column, compute from exit_price-entry_price}
        {--short : If auto-pnl, treat trades as short (entry-exit)}
        {--win= : Override boolean win column on trades (e.g. is_win)}
        {--dry-run : Do not write, only print aggregates}';
    protected $description = 'Recompute profile_results metrics per profile over a rolling window with flexible mapping';

    public function handle(): int
    {
        $days   = (int)$this->option('days');
        $limit  = (int)$this->option('limit');
        $dryRun = (bool)$this->option('dry-run');

        $tradesTable = $this->option('table') ?: config('profiles.trades_table', 'trades');

        // Mapping: either FK (profiles.id == trades.<fk>) OR named map profiles.<LHS> == trades.<RHS>
        $mapOpt = $this->option('map'); // e.g. name=forecast_type
        $fkOpt  = $this->option('fk');

        $tsOpt  = $this->option('ts');
        $pnlOpt = $this->option('pnl');
        $winOpt = $this->option('win');
        $autoPnl = (bool)$this->option('auto-pnl');
        $autoShort = (bool)$this->option('short');

        $windowEnd   = now();
        $windowStart = $days > 0 ? now()->subDays($days)->startOfDay() : null;

        // Detect columns on trades
        $executedAtCol = $tsOpt ?: $this->firstExistingColumn($tradesTable, ['executed_at','filled_at','closed_at','created_at','timestamp','date']);
        $pnlCol        = $pnlOpt ?: $this->firstExistingColumn($tradesTable, ['pnl','profit','realized_pnl','realized','net_pnl','pnl_net']);
        $isWinCol      = $winOpt ?: $this->firstExistingColumn($tradesTable, ['is_win','win','won','result']);

        $this->line('--- DETECTED ON TRADES ---');
        $this->line('Table: '.$tradesTable);
        $this->line('Columns: '.implode(', ', $this->listColumns($tradesTable)));
        $this->line('Timestamp: '.($executedAtCol ?: 'NOT FOUND'));
        $this->line('PnL col  : '.($pnlCol ?: 'NOT FOUND'));
        $this->line('Win col  : '.($isWinCol ?: 'NOT FOUND'));
        $this->line('Map      : '.($mapOpt ?: ('id='.$fkOpt)));
        $this->line('AutoPnL  : '.($autoPnl ? ('ON ('.($autoShort?'short':'long').')') : 'OFF'));
        $this->line('--------------------------'.PHP_EOL);

        if (!$executedAtCol) {
            $this->error("No timestamp column on {$tradesTable}. Set --ts= to an existing column.");
            return self::FAILURE;
        }

        // Load profiles
        $profiles = DB::table('strategy_profiles')
            ->when(Schema::hasColumn('strategy_profiles','enabled'), fn($q) => $q->where('enabled', 1))
            ->orderBy('id','asc')
            ->limit($limit)
            ->select('id','name')
            ->get();

        $this->line(sprintf(
            "--- PROFILES RECOMPUTE ---\nWindow: %s..%s (%s)\nProfiles: %d",
            $windowStart?->toDateTimeString() ?? 'ALL',
            $windowEnd->toDateTimeString(),
            $days > 0 ? ($days.' days') : 'no date filter',
            $profiles->count()
        ));

        $wrote = 0;

        foreach ($profiles as $p) {
            $pid = $p->id;

            // Build base query based on mapping
            if ($mapOpt) {
                // parse "left=right" e.g. name=forecast_type
                if (!str_contains($mapOpt, '=')) {
                    $this->error('--map must be in form profilesCol=tradesCol, e.g. name=forecast_type');
                    return self::FAILURE;
                }
                [$left, $right] = array_map('trim', explode('=', $mapOpt, 2));
                $leftVal = $p->{$left} ?? null;
                if ($leftVal === null) {
                    $base = DB::table($tradesTable)->whereRaw('1=0');
                } else {
                    $base = DB::table($tradesTable)->where($right, $leftVal);
                }
            } else {
                // FK mode profiles.id == trades.<fk>
                $fkCol = $fkOpt ?: (config('profiles.trades_fk_col') ?: $this->firstExistingColumn($tradesTable, ['strategy_profile_id','profile_id','strategy_id','profile','profileId','profileID']));
                if (!$fkCol) {
                    $this->error("No FK column on trades and no --map provided. Use --map=name=forecast_type or set --fk=");
                    return self::FAILURE;
                }
                $base = DB::table($tradesTable)->where($fkCol, $pid);
            }

            if ($windowStart) {
                $base->whereBetween($executedAtCol, [$windowStart, $windowEnd]);
            }

            // Aggregates
            $trades = (clone $base)->count();

            // PnL
            if ($pnlCol) {
                $pnlSum = (clone $base)->sum($pnlCol);
            } elseif ($autoPnl && Schema::hasColumn($tradesTable, 'entry_price') && Schema::hasColumn($tradesTable, 'exit_price')) {
                $rows = (clone $base)->select('entry_price','exit_price')->get();
                $pnlSum = 0;
                foreach ($rows as $r) {
                    if ($r->entry_price !== null && $r->exit_price !== null) {
                        $pnlSum += $this->calcAutoPnl($r->entry_price, $r->exit_price, $autoShort);
                    }
                }
            } else {
                $pnlSum = 0;
            }

            // Wins
            if ($winOpt && Schema::hasColumn($tradesTable, $winOpt)) {
                $wins = (clone $base)->where($winOpt, 1)->count();
            } elseif ($isWinCol && Schema::hasColumn($tradesTable, $isWinCol) && $isWinCol !== 'result') {
                $wins = (clone $base)->where($isWinCol, 1)->count();
            } elseif ($pnlCol) {
                $wins = (clone $base)->where($pnlCol,'>',0)->count();
            } elseif ($autoPnl && Schema::hasColumn($tradesTable, 'entry_price') && Schema::hasColumn($tradesTable, 'exit_price')) {
                $rows = (clone $base)->select('entry_price','exit_price')->get();
                $wins = 0;
                foreach ($rows as $r) {
                    if ($r->entry_price !== null && $r->exit_price !== null) {
                        $p = $this->calcAutoPnl($r->entry_price, $r->exit_price, $autoShort);
                        if ($p > 0) $wins++;
                    }
                }
            } else {
                $wins = 0;
            }

            $minTs = (clone $base)->min($executedAtCol);
            $maxTs = (clone $base)->max($executedAtCol);
            $winRate = $trades > 0 ? round(($wins / $trades) * 100, 2) : 0;

            if ($this->getOutput()->isVerbose()) {
                $this->line(sprintf(
                    "Profile #%d (%s)  trades=%d  pnl=%.2f  win%%=%.2f  %s..%s",
                    $pid, $p->name, $trades, $pnlSum, $winRate, $minTs ?? '—', $maxTs ?? '—'
                ));
            }

            if ($dryRun) continue;

            $payload = [
                'strategy_profile_id' => $pid,
                'trades'       => $trades,
                'pnl'          => $pnlSum ?? 0,
                'win_rate'     => $winRate,
                'window_start' => $minTs,
                'window_end'   => $maxTs,
                'updated_at'   => now(),
            ];

            $existing = DB::table('profile_results')->where('strategy_profile_id', $pid)->first();
            if ($existing) {
                DB::table('profile_results')->where('id', $existing->id)->update($payload);
            } else {
                $payload['created_at'] = now();
                DB::table('profile_results')->insert($payload);
            }
            $wrote++;
        }

        if ($dryRun) {
            $this->info('Dry-run complete. No changes written.');
        } else {
            $this->info("Done. Rows written/updated: {$wrote}.");
        }
        return self::SUCCESS;
    }

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if (Schema::hasColumn($table, $c)) return $c;
        }
        return null;
    }

    private function listColumns(string $table): array
    {
        try {
            return Schema::getColumnListing($table);
        } catch (\Throwable $e) {
            return ['<could not read columns: '.$e->getMessage().'>'];
        }
    }

    private function calcAutoPnl($entry, $exit, bool $short): float
    {
        if ($entry === null || $exit === null) return 0.0;
        return $short ? ($entry - $exit) : ($exit - $entry);
    }
}

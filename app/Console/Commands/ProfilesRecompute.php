<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProfilesRecompute extends Command
{
    protected $signature = 'profiles:recompute
        {--days=10 : Lookback window in days (0 = all)}
        {--table=trades : Trades table}
        {--ts=created_at : Timestamp column on trades}
        {--pnl= : Numeric PnL column on trades}
        {--auto-pnl : If no PnL column, compute exit_price - entry_price (or entry-exit with --short)}
        {--short : Use entry - exit in auto-pnl (short) }
        {--win= : Boolean win column on trades}
        {--split= : Distribute trades across profiles sharing the same external_key. Format: hash:<col1>[,+<col2>...] }
        {--limit=0 : Optional cap on number of profiles processed (0 = all)}
        {--dry-run : Print only, do not write}';

    protected $description = 'Recompute profile_results using strategy_profiles.external_key mapped to trades.forecast_type, optionally splitting trades among profiles via stable hashing.';

    public function handle(): int
    {
        $days     = (int)$this->option('days');
        $table    = (string)$this->option('table');
        $ts       = (string)$this->option('ts');
        $pnlCol   = $this->option('pnl') ?: null;
        $autoPnL  = (bool)$this->option('auto-pnl');
        $short    = (bool)$this->option('short');
        $winCol   = $this->option('win') ?: null;
        $splitOpt = $this->option('split') ?: null;
        $limit    = (int)$this->option('limit');
        $dryRun   = (bool)$this->option('dry-run');

        if (!Schema::hasTable($table)) { $this->error("Trades table '{$table}' not found."); return self::FAILURE; }
        foreach ([$ts] as $c) { if (!Schema::hasColumn($table, $c)) { $this->error("Missing column {$table}.{$c}"); return self::FAILURE; } }
        if (!Schema::hasColumn($table,'forecast_type')) { $this->error("Missing column {$table}.forecast_type"); return self::FAILURE; }

        $windowEnd   = now();
        $windowStart = $days > 0 ? now()->subDays($days)->startOfDay() : null;

        // Load profiles grouped by external_key
        $profilesQ = DB::table('strategy_profiles')
            ->when(Schema::hasColumn('strategy_profiles','enabled'), fn($q) => $q->where('enabled',1))
            ->select('id','name','external_key')
            ->orderBy('id');
        if ($limit > 0) $profilesQ->limit($limit);
        $profiles = $profilesQ->get();

        // Group by external_key and also include NULL (will result in zero rows unless split applies)
        $groups = [];
        foreach ($profiles as $p) {
            $key = $p->external_key ?? '__NULL__';
            if (!isset($groups[$key])) $groups[$key] = [];
            $groups[$key][] = $p;
        }

        $this->line(sprintf(
            "--- PROFILES RECOMPUTE ---%sWindow: %s..%s (%s)%sProfiles: %d  Groups: %d",
            PHP_EOL,
            $windowStart?->toDateTimeString() ?? 'ALL',
            $windowEnd->toDateTimeString(),
            $days > 0 ? ($days.' days') : 'no date filter',
            PHP_EOL,
            count($profiles), count($groups)
        ));

        foreach ($groups as $extKey => $plist) {
            // Skip NULL group (no mapping)
            if ($extKey === '__NULL__') {
                foreach ($plist as $p) {
                    $this->writeResult($p->id, 0, 0, 0, null, null, $dryRun);
                }
                continue;
            }

            // Build base trade query for this external_key
            $base = DB::table($table)->where('forecast_type', $extKey);
            if ($windowStart) $base->whereBetween($ts, [$windowStart, $windowEnd]);

            // If no split option and only one profile -> aggregate directly
            if (!$splitOpt || count($plist) === 1) {
                $p = $plist[0];
                $agg = $this->aggregateTrades($base, $table, $ts, $pnlCol, $winCol, $autoPnL, $short);
                $this->logLine($p->name, $extKey, $agg);
                $this->writeResult($p->id, $agg['trades'], $agg['pnl'], $agg['win_rate'], $agg['min_ts'], $agg['max_ts'], $dryRun);
                for ($i=1; $i<count($plist); $i++) {
                    $pp = $plist[$i];
                    $this->logLine($pp->name, $extKey, ['trades'=>0,'pnl'=>0,'win_rate'=>0,'min_ts'=>null,'max_ts'=>null]);
                    $this->writeResult($pp->id, 0, 0, 0, null, null, $dryRun);
                }
            } else {
                // Split mode
                if (strpos($splitOpt, 'hash:') !== 0) {
                    $this->error("Unsupported --split format. Use: --split=hash:<col1>[,+<col2>...]");
                    return self::FAILURE;
                }
                $cols = array_map('trim', explode(',', substr($splitOpt, 5)));
                foreach ($cols as $c) {
                    if (!Schema::hasColumn($table, $c)) {
                        $this->error("Split column '{$c}' not found on {$table}.");
                        return self::FAILURE;
                    }
                }

                // Initialize per-profile accumulators
                $acc = [];
                foreach ($plist as $p) {
                    $acc[$p->id] = ['trades'=>0,'pnl'=>0.0,'wins'=>0,'min_ts'=>null,'max_ts'=>null];
                }

                // Select needed columns
                $selectCols = array_merge($cols, [$ts]);
                if ($pnlCol) { $selectCols[] = $pnlCol; }
                if ($autoPnL && Schema::hasColumn($table,'entry_price') && Schema::hasColumn($table,'exit_price')) {
                    $selectCols[] = 'entry_price'; $selectCols[] = 'exit_price';
                }

                $rows = (clone $base)->select($selectCols)->get();
                foreach ($rows as $r) {
                    // Compute pnl and win
                    if ($pnlCol && isset($r->{$pnlCol})) {
                        $pnl = (float)$r->{$pnlCol};
                        $win = $pnl > 0 ? 1 : 0;
                    } elseif ($autoPnL && isset($r->entry_price) && isset($r->exit_price)) {
                        $pnl = ($r->entry_price !== null && $r->exit_price !== null)
                            ? ($short ? ($r->entry_price - $r->exit_price) : ($r->exit_price - $r->entry_price))
                            : 0.0;
                        $win = $pnl > 0 ? 1 : 0;
                    } else {
                        $pnl = 0.0; $win = 0;
                    }

                    // Hash bucket
                    $buf = '';
                    foreach ($cols as $c) { $buf .= '|' . (string)($r->{$c}); }
                    $hash = crc32($buf);
                    $idx = $hash % count($plist);
                    $pid = $plist[$idx]->id;

                    // Accumulate
                    $acc[$pid]['trades'] += 1;
                    $acc[$pid]['pnl']    += $pnl;
                    $acc[$pid]['wins']   += $win;
                    $tsv = $r->{$ts};
                    if ($tsv !== null) {
                        if ($acc[$pid]['min_ts'] === null || $tsv < $acc[$pid]['min_ts']) $acc[$pid]['min_ts'] = $tsv;
                        if ($acc[$pid]['max_ts'] === null || $tsv > $acc[$pid]['max_ts']) $acc[$pid]['max_ts'] = $tsv;
                    }
                }

                // Write per profile
                foreach ($plist as $p) {
                    $a = $acc[$p->id];
                    $winRate = $a['trades'] > 0 ? round(($a['wins'] / $a['trades']) * 100, 2) : 0;
                    $this->logLine($p->name, $extKey, [
                        'trades'=>$a['trades'],'pnl'=>$a['pnl'],'win_rate'=>$winRate,'min_ts'=>$a['min_ts'],'max_ts'=>$a['max_ts']
                    ]);
                    $this->writeResult($p->id, $a['trades'], $a['pnl'], $winRate, $a['min_ts'], $a['max_ts'], $dryRun);
                }
            }
        }

        if ($dryRun) $this->info('Dry-run complete.'); else $this->info('Done.');
        return self::SUCCESS;
    }

    private function aggregateTrades($base, $table, $ts, $pnlCol, $winCol, $autoPnL, $short): array
    {
        $trades = (clone $base)->count();

        if ($pnlCol && Schema::hasColumn($table, $pnlCol)) {
            $pnl = (clone $base)->sum($pnlCol);
            $wins = (clone $base)->where($pnlCol, '>', 0)->count();
        } elseif ($autoPnL && Schema::hasColumn($table,'entry_price') && Schema::hasColumn($table,'exit_price')) {
            $rows = (clone $base)->select('entry_price','exit_price')->get();
            $pnl = 0; $wins = 0;
            foreach ($rows as $r) {
                if ($r->entry_price !== null && $r->exit_price !== null) {
                    $tmp = $short ? ($r->entry_price - $r->exit_price) : ($r->exit_price - $r->entry_price);
                    $pnl += $tmp; if ($tmp > 0) $wins++;
                }
            }
        } else {
            $pnl = 0; $wins = 0;
        }

        $minTs = (clone $base)->min($ts);
        $maxTs = (clone $base)->max($ts);
        $winRate = $trades > 0 ? round(($wins / $trades) * 100, 2) : 0;

        return ['trades'=>$trades,'pnl'=>$pnl,'win_rate'=>$winRate,'min_ts'=>$minTs,'max_ts'=>$maxTs];
    }

    private function writeResult($profileId, $trades, $pnl, $winRate, $minTs, $maxTs, $dryRun)
    {
        $payload = [
            'strategy_profile_id' => $profileId,
            'trades' => $trades,
            'pnl' => $pnl,
            'win_rate' => $winRate,
            'window_start' => $minTs,
            'window_end' => $maxTs,
            'updated_at' => now(),
        ];
        if ($dryRun) return;
        $existing = DB::table('profile_results')->where('strategy_profile_id',$profileId)->first();
        if ($existing) DB::table('profile_results')->where('id',$existing->id)->update($payload);
        else { $payload['created_at'] = now(); DB::table('profile_results')->insert($payload); }
    }

    private function logLine($name, $extKey, $agg)
    {
        $this->line(sprintf("%s [%s] trades=%d pnl=%.2f win%%=%.2f  %s..%s",
            $name, $extKey, $agg['trades'], $agg['pnl'], $agg['win_rate'], $agg['min_ts'] ?? '—', $agg['max_ts'] ?? '—'
        ));
    }
}

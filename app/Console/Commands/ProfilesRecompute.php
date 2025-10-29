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
        {--limit=0}
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

        if (!Schema::hasTable($table)) { $this->error("Table {$table} not found"); return self::FAILURE; }
        foreach ([$ts,'strategy_profile_id'] as $c) {
            if (!Schema::hasColumn($table,$c)) { $this->error("Missing {$table}.{$c}"); return self::FAILURE; }
        }

        $end   = now();
        $start = $days > 0 ? now()->subDays($days)->startOfDay() : null;
        
        $base = DB::table($table)->whereNotNull('strategy_profile_id');
        //if ($start) $base->whereBetween($ts, [$start, $end]);

        //$profileIds = (clone $base)->distinct()->pluck('strategy_profile_id')->filter()->values();

        $limit = (int) $this->option('limit');

        // $profilesQuery = \App\Models\StrategyProfile::query()
        //     ->when(\Schema::hasColumn('strategy_profiles','enabled'), fn($q) => $q->where('enabled',1))
        //     ->orderBy('id');

        // if ($limit > 0) {
        //     $profilesQuery->limit($limit);
        // }

        // $profileIds = $profilesQuery->pluck('id')->all();

        if ($table === 'simulated_trades') {
            // $base = DB::table('simulated_trades as s')
            //     ->join('trades as t', 't.id', '=', 's.trade_id')
            //     ->whereNotNull('t.strategy_profile_id');

            // $joinCandidates = ['trade_id','source_trade_id','parent_trade_id','orig_trade_id','trades_id'];
            // $joinKey = null;
            // foreach ($joinCandidates as $c) {
            //     if (Schema::hasColumn('simulated_trades', $c)) { $joinKey = $c; break; }
            // }
            // if (!$joinKey) { $this->error('No join key found on simulated_trades.'); return 1; }

            // $base = DB::table('simulated_trades as s')
            //     ->join('trades as t', 't.id', '=', DB::raw("s.`$joinKey`"))
            //     ->whereNotNull('t.strategy_profile_id');

            // $profileIds = (clone $base)->select('t.strategy_profile_id')->distinct()->orderBy('t.strategy_profile_id')->pluck('t.strategy_profile_id')->all();

            // foreach ($profileIds as $pid) {
            //     $rows = (clone $base)
            //         ->where('t.strategy_profile_id', $pid)
            //         ->when($days > 0, fn($q) => $q->whereBetween(DB::raw('s.`'+$ts+'`'), [$start, $end]))
            //         ->whereNotNull('s.entry_price')->whereNotNull('s.exit_price')
            //         ->select(['s.id','s.ticker', DB::raw('s.`'+$ts+'` as ts'),'s.entry_price','s.exit_price','s.result'])
            //         ->get();

            //     $totalPnl = 0; $wins = 0; $n = 0;
            //     foreach ($rows as $r) {
            //         $p = ($r->exit_price ?? 0) - ($r->entry_price ?? 0);
            //         $totalPnl += $p;
            //         $wins += ($p > 0) ? 1 : 0;
            //         $n++;
            //     }
            //     $winRate = $n ? ($wins / $n) * 100 : 0;
            //     $this->line("PID={$pid} agg: n={$n} pnl={$totalPnl} win%=".number_format($winRate,2)); 
                
            //     $minTs = (clone $q)->min($ts);
            //     $maxTs = (clone $q)->max($ts);
            //     $winRate = $trades>0 ? round($wins/$trades*100,2) : 0;

            //     $payload = [
            //         'strategy_profile_id' => $pid,
            //         'trades' => $trades,
            //         'pnl' => $pnl,
            //         'win_rate' => $winRate,
            //         'window_start' => $minTs,
            //         'window_end'   => $maxTs,
            //         'updated_at'   => now(),
            //     ];
            //     $existing = DB::table('profile_results')->where('strategy_profile_id',$pid)->first();
            //     if ($existing) {
            //         DB::table('profile_results')->where('id',$existing->id)->update($payload);
            //     } else {
            //         $payload['created_at'] = now();
            //         DB::table('profile_results')->insert($payload);
            //     }

            //     $name = DB::table('strategy_profiles')->where('id',$pid)->value('name') ?? ('#'.$pid);
            //     $this->line("$name trades=$trades pnl=$pnl win%=$winRate");

            //     $this->line("Aggregating PID={$pid} window={$start}..{$end}");   
            // }

            /*SCHEMA NO JOIN*/
            $base = DB::table($table . ' as s')->whereNotNull('s.strategy_profile_id');

            $profileIds = (clone $base)
                ->select('s.strategy_profile_id')
                ->distinct()
                ->orderBy('s.strategy_profile_id')
                ->pluck('s.strategy_profile_id')
                ->all();

            foreach ($profileIds as $pid) {
                $rows = (clone $base)
                    ->where('s.strategy_profile_id', $pid)
                    ->when($days > 0, fn($q) => $q->whereBetween(DB::raw("s.`$ts`"), [$start, $end]))
                    ->select([
                        's.id',
                        's.ticker',
                        DB::raw("s.`$ts` as ts"),
                        's.entry_price',
                        's.exit_price',
                        // 's.result', // optional win flag if you use it
                    ])
                    ->get();

                $trades = $rows->count();
                $totalPnl = 0.0; $wins = 0;

                foreach ($rows as $r) {
                    $entry = $r->entry_price !== null ? (float)$r->entry_price : null;
                    $exit  = $r->exit_price  !== null ? (float)$r->exit_price  : null;
                    $p = (!is_null($entry) && !is_null($exit)) ? ($exit - $entry) : 0.0;
                    $totalPnl += $p;
                    if ($p > 0) $wins++;
                }

                $winRate = $trades ? $wins / $trades * 100.0 : 0.0;
                $minTs = $rows->min('ts');
                $maxTs = $rows->max('ts');

                DB::table('profile_results')->updateOrInsert(
                    [
                        'strategy_profile_id' => $pid,
                        'window_start' => $minTs,
                        'window_end'   => $maxTs,
                    ],
                    [
                        'trades'     => $trades,
                        'pnl'        => $totalPnl,
                        'win_rate'   => $winRate,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $this->line(sprintf("PID=%s agg: n=%d pnl=%.2f win%%=%.2f", $pid, $trades, $totalPnl, $winRate));
            }
            /*SCHEMA NO JOIN*/
        } else {
            $base = DB::table($table)->whereNotNull('strategy_profile_id');
        

        $profileIds = (clone $base)
            ->select('t.strategy_profile_id')
            ->distinct()
            ->orderBy('t.strategy_profile_id')
            ->pluck('t.strategy_profile_id')
            ->all();    

        $this->line("--- PROFILES RECOMPUTE (FK) ---");
        $this->info('Profiles selected: '.count($profileIds));
        foreach ($profileIds as $pid) {
            $q = (clone $base)->where('strategy_profile_id', $pid);
            $trades = (clone $q)->count();
            $pnl=0; $wins=0;

            if ($pnlCol && Schema::hasColumn($table,$pnlCol)) {
                $pnl  = (clone $q)->sum($pnlCol);
                $wins = (clone $q)->where($pnlCol,'>',0)->count();
            } elseif ($autoPnL && Schema::hasColumn($table,'entry_price') && Schema::hasColumn($table,'exit_price')) {
                //$rows = (clone $q)->select('entry_price','exit_price')->get();
                
                // foreach ($rows as $r) {
                //     if ($r->entry_price!==null && $r->exit_price!==null) {
                //         $p = $short ? ($r->entry_price - $r->exit_price) : ($r->exit_price - $r->entry_price);
                //         $pnl += $p; if ($p>0) $wins++;
                //     }
                // }

                $rows = (clone $base)
                            ->where('t.strategy_profile_id', $pid)
                            ->when($days > 0, fn($q) => $q->whereBetween('s.' . $ts, [$start, $end]))
                            ->select([
                                's.id',
                                's.ticker',
                                DB::raw('s.' . $ts . ' as ts'),
                                's.entry_price',
                                's.exit_price',
                                's.result',
                            ])
                            ->get();

                $totalPnl = 0; $wins = 0; $n = 0;
                foreach ($rows as $r) {
                    $p = ($r->exit_price ?? 0) - ($r->entry_price ?? 0);
                    $totalPnl += $p;
                    $wins += ($p > 0) ? 1 : 0;
                    $n++;
                }
                $winRate = $n ? ($wins / $n) * 100 : 0;
                $this->line("PID={$pid} agg: n={$n} pnl={$totalPnl} win%=".number_format($winRate,2));
            }

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
            $this->line("$name trades=$trades pnl=$pnl win%=$winRate");

            $this->line("Aggregating PID={$pid} window={$start}..{$end}");
        }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Services\StrategyEngine;
use App\Models\Stock;
use Carbon\Carbon;

class BacktestGridCommand extends Command
{
    protected $signature = 'backtest:grid
        {--days=5 : How many previous trading days to test}
        {--tickers= : Comma-separated tickers (default: all in stocks table)}
        {--range=15 : Comma-separated opening range minutes options, e.g. 10,15,20}
        {--entrybuf=0 : Comma-separated entry buffer percents, e.g. 0,0.05,0.1}
        {--retest=1 : Require retest? 1 or 0}
        {--slbuf=0 : Comma-separated SL buffer percents, e.g. 0,0.25,0.5}
        {--tp=1x2 : TP levels; formats: 1x2 (1R,2R) or 1x2x3 (1R,2R,3R); multiple sets with ;}
        {--trail=0 : Enable trailing after first TP (0/1)}
        {--start=09:30 : Session start HH:MM NY time}
        {--end=12:00 : Session end HH:MM NY time}
    ';

    protected $description = 'Grid search ORB+Retest parameters over recent days and tickers, output CSV summary.';

    public function handle(StrategyEngine $engine)
    {
        $days     = (int)$this->option('days');
        $tickers  = $this->option('tickers');
        $rangeOpt = $this->parseFloatList($this->option('range'));
        $entryBuf = $this->parseFloatList($this->option('entrybuf'));
        $retest   = (int)$this->option('retest') === 1;
        $slbufOpt = $this->parseFloatList($this->option('slbuf'));
        $tpOpt    = $this->parseTpSets($this->option('tp'));
        $trail    = (int)$this->option('trail') === 1;
        $startStr = (string)$this->option('start');
        $endStr   = (string)$this->option('end');

        $list = [];
        if ($tickers) {
            $list = array_filter(array_map('trim', explode(',', $tickers)));
        } else {
            $list = \App\Models\Stock::pluck('ticker')->map(fn($t)=>trim($t))->toArray();
        }

        if (empty($list)) {
            $this->error('No tickers found. Populate stocks table or pass --tickers=');
            return 1;
        }

        $nyNow = Carbon::now('America/New_York');
        $dates = [];
        // Collect previous trading days
        $d = $nyNow->copy();
        for ($i=0; count($dates)<$days; $i++) {
            $d = $engine->previousTradingDay($d);
            $dates[] = $d->copy();
        }

        $results = []; // rows for CSV
        $totalTests = 0;

        foreach ($rangeOpt as $rng) {
            foreach ($entryBuf as $eb) {
                foreach ($slbufOpt as $sb) {
                    foreach ($tpOpt as $tpSet) {
                        $params = [
                            'range_minutes' => (int)$rng,
                            'entry_buffer_percent' => (float)$eb,
                            'require_retest' => $retest,
                            'sl_buffer_percent' => (float)$sb,
                            'tp_levels' => $tpSet,
                            'enable_trailing_stop' => $trail,
                            'session_start' => $startStr,
                            'session_end' => $endStr,
                        ];

                        $summary = [
                            'trades' => 0,
                            'wins'   => 0,   // TP>=1
                            'tp2'    => 0,
                            'loss'   => 0,
                            'closed' => 0,
                            'sumR'   => 0.0,
                        ];

                        foreach ($dates as $date) {
                            foreach ($list as $ticker) {
                                $one = $engine->simulateOne($ticker, $date, $params);
                                if (!$one) continue;

                                $summary['trades']++;
                                $r = $one['r_multiple'] ?? 0.0;

                                if (str_starts_with($one['status'], 'TP')) {
                                    $summary['wins']++;
                                    if ($one['status'] === 'TP2' || $one['status'] === 'TP3') $summary['tp2']++;
                                } elseif ($one['status'] === 'SL' || $one['status'] === 'TSL') {
                                    $summary['loss']++;
                                } elseif ($one['status'] === 'closed') {
                                    $summary['closed']++;
                                }

                                $summary['sumR'] += (float) $r;
                            }
                        }

                        $winrate = $summary['trades'] > 0 ? round(100.0 * $summary['wins'] / $summary['trades'], 2) : 0;
                        $avgR    = $summary['trades'] > 0 ? round($summary['sumR'] / $summary['trades'], 3) : 0;

                        $results[] = [
                            'range_min' => $rng,
                            'entry_buf_pct' => $eb,
                            'retest' => $retest ? 1 : 0,
                            'sl_buf_pct' => $sb,
                            'tp_set' => implode('x', $tpSet),
                            'trail' => $trail ? 1 : 0,
                            'days'  => $days,
                            'tickers' => count($list),
                            'trades' => $summary['trades'],
                            'wins'   => $summary['wins'],
                            'tp2'    => $summary['tp2'],
                            'loss'   => $summary['loss'],
                            'closed' => $summary['closed'],
                            'winrate_pct' => $winrate,
                            'avg_R'  => $avgR,
                            'sum_R'  => round($summary['sumR'], 3),
                        ];

                        $totalTests++;
                        $this->line("Tested config #{$totalTests}: range={$rng}, eb={$eb}%, slb={$sb}%, tp=".implode('x',$tpSet).", trail=".($trail?'1':'0')." => trades={$summary['trades']} winrate={$winrate}% avgR={$avgR}");
                    }
                }
            }
        }

        if (empty($results)) {
            $this->warn('No results. Try increasing days or relaxing filters.');
            return 0;
        }

        // Save CSV
        $header = array_keys($results[0]);
        $csv = implode(',', $header)."\n";
        foreach ($results as $row) {
            $csv .= implode(',', array_map(function($v){
                if (is_null($v)) return '';
                $s = (string)$v;
                $s = str_replace(["\r","\n"], ' ', $s);
                $s = str_replace('"','""',$s);
                return (strpos($s, ',') !== false) ? '"'.$s.'"' : $s;
            }, $row))."\n";
        }

        $dir = 'exports';
        if (!Storage::exists($dir)) Storage::makeDirectory($dir);
        $file = 'backtest_grid_'.now()->format('Ymd_His').'.csv';
        Storage::put($dir.'/'.$file, $csv);

        $this->info('Grid backtest saved: storage/app/'.$dir.'/'.$file);
        return 0;
    }

    private function parseFloatList(?string $s): array
    {
        if (!$s) return [];
        return array_values(array_filter(array_map(function($x){
            return is_numeric(trim($x)) ? (float)trim($x) : null;
        }, explode(',', $s)), function($v){ return !is_null($v); }));
    }

    private function parseTpSets(?string $s): array
    {
        // examples: "1x2" or "1x2x3;1x3"
        if (!$s) return [[1.0,2.0]];
        $s = trim($s);
        $sets = [];
        foreach (explode(';', $s) as $part) {
            $levels = [];
            foreach (explode('x', trim($part)) as $lv) {
                if (is_numeric($lv)) $levels[] = (float)$lv;
            }
            if (!empty($levels)) $sets[] = $levels;
        }
        return !empty($sets) ? $sets : [[1.0,2.0]];
    }
}

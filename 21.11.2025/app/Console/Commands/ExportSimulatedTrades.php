<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExportSimulatedTrades extends Command
{
    protected $signature = 'trades:export 
        {--from= : From date YYYY-MM-DD} 
        {--to= : To date YYYY-MM-DD}
        {--all : Export all rows}';

    protected $description = 'Export simulated_trades to CSV (storage/app/exports).';

    public function handle()
    {
        $from = $this->option('from');
        $to   = $this->option('to');
        $all  = $this->option('all');

        $q = DB::table('simulated_trades')
            ->select([
                'id','date','ticker','entry_price','exit_price','sl_price','tp1','tp2',
                'status','fees','net_profit','forecast_type','forecast_score','trend_rating',
                'earnings_day','executed_on_nordnet','created_at'
            ])
            ->orderBy('date','desc')
            ->orderBy('ticker');

        if (!$all) {
            if ($from) $q->where('date', '>=', $from);
            if ($to)   $q->where('date', '<=', $to);
            if (!$from && !$to) {
                // default: last 30 days
                $q->where('date', '>=', now()->subDays(30)->toDateString());
            }
        }

        $rows = $q->get();

        if ($rows->isEmpty()) {
            $this->warn('No rows found for the chosen range.');
            return 0;
        }

        $header = [
            'id','date','ticker','entry_price','exit_price','sl_price','tp1','tp2',
            'status','fees','net_profit','forecast_type','forecast_score','trend_rating',
            'earnings_day','executed_on_nordnet','created_at'
        ];

        $csv = implode(',', $header)."\n";
        foreach ($rows as $r) {
            $line = [
                $r->id,
                $r->date,
                $r->ticker,
                $r->entry_price,
                $r->exit_price,
                $r->sl_price,
                $r->tp1,
                $r->tp2,
                $r->status,
                $r->fees,
                $r->net_profit,
                $r->forecast_type,
                $r->forecast_score,
                $r->trend_rating,
                $r->earnings_day ? 1 : 0,
                $r->executed_on_nordnet ? 1 : 0,
                $r->created_at,
            ];
            $csv .= implode(',', array_map(function($v) {
                if (is_null($v)) return '';
                $s = (string)$v;
                $s = str_replace(["\r","\n"], ' ', $s);
                $s = str_replace('"', '""', $s);
                return (strpos($s, ',') !== false) ? '"'.$s.'"' : $s;
            }, $line))."\n";
        }

        $dir = 'exports';
        if (!Storage::exists($dir)) {
            Storage::makeDirectory($dir);
        }

        $filename = 'simulated_trades_'.now()->format('Ymd_His').'.csv';
        Storage::put($dir.'/'.$filename, $csv);

        $this->info('Exported '.count($rows).' rows -> storage/app/'.$dir.'/'.$filename);
        return 0;
    }
}

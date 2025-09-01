<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class BacktestController extends Controller
{
    public function index()
    {
        $table = 'simulated_trades';
        $results = [];

        if (Schema::hasTable($table)) {
            $cols = Schema::getColumnListing($table);
            $select = [];

            // date/time
            if (in_array('date', $cols))       $select[] = 'date';
            elseif (in_array('executed_at', $cols)) $select[] = 'executed_at as date';
            elseif (in_array('created_at', $cols))  $select[] = 'created_at as date';
            else $select[] = DB::raw('NOW() as date');

            // ticker / symbol
            if (in_array('ticker', $cols))     $select[] = 'ticker';
            elseif (in_array('symbol', $cols)) $select[] = 'symbol as ticker';
            else $select[] = DB::raw("'-' as ticker");

            // status
            if (in_array('status', $cols))     $select[] = 'status';
            elseif (in_array('state', $cols))  $select[] = 'state as status';
            else $select[] = DB::raw("'closed' as status");

            // prices
            if (in_array('entry_price', $cols))      $select[] = 'entry_price';
            elseif (in_array('entry', $cols))        $select[] = 'entry as entry_price';
            else $select[] = DB::raw('NULL as entry_price');

            if (in_array('sl_price', $cols))         $select[] = 'sl_price';
            elseif (in_array('stop', $cols))         $select[] = 'stop as sl_price';
            elseif (in_array('stop_loss', $cols))    $select[] = 'stop_loss as sl_price';
            else $select[] = DB::raw('NULL as sl_price');

            if (in_array('exit_price', $cols))       $select[] = 'exit_price';
            elseif (in_array('exit', $cols))         $select[] = 'exit as exit_price';
            elseif (in_array('close_price', $cols))  $select[] = 'close_price as exit_price';
            else $select[] = DB::raw('NULL as exit_price');

            // qty / size
            if (in_array('qty', $cols))              $select[] = 'qty';
            elseif (in_array('quantity', $cols))     $select[] = 'quantity as qty';
            elseif (in_array('size', $cols))         $select[] = 'size as qty';
            else $select[] = DB::raw('1 as qty');

            // fees_bps
            if (in_array('fees_bps', $cols))         $select[] = 'fees_bps';
            elseif (in_array('fee_bps', $cols))      $select[] = 'fee_bps as fees_bps';
            else $select[] = DB::raw('0 as fees_bps');

            // side / direction
            if (in_array('side', $cols))             $select[] = 'side';
            elseif (in_array('direction', $cols))    $select[] = 'direction as side';
            elseif (in_array('type', $cols))         $select[] = 'type as side';
            else $select[] = DB::raw("'long' as side");

            $rows = DB::table($table)->select($select)->orderByDesc('date')->limit(200)->get();

            foreach ($rows as $r) {
                $results[] = [
                    'date'   => (string)($r->date ?? ''),
                    'ticker' => (string)($r->ticker ?? '-'),
                    'status' => (string)($r->status ?? 'closed'),
                    'entry'  => isset($r->entry_price) ? (float)$r->entry_price : null,
                    'sl'     => isset($r->sl_price) ? (float)$r->sl_price : null,
                    'exit'   => isset($r->exit_price) ? (float)$r->exit_price : null,
                    'qty'    => isset($r->qty) ? (float)$r->qty : 1,
                    'fees_bps' => isset($r->fees_bps) ? (float)$r->fees_bps : 0.0,
                    'side'   => (string)($r->side ?? 'long'),
                ];
            }
        } else {
            $results = [
                ['date'=>now()->toDateTimeString(),'ticker'=>'DEMO','status'=>'closed','entry'=>100,'sl'=>98,'exit'=>101,'qty'=>1,'fees_bps'=>10,'side'=>'long'],
            ];
        }

        // View fallback chain
        if (View::exists('backtest.index'))       return view('backtest.index', compact('results'));
        if (View::exists('backtest'))             return view('backtest', compact('results'));
        if (View::exists('results.backtest'))     return view('results.backtest', compact('results'));

        // As a last resort, return a tiny inline view
        return response()->view('backtest_inline', ['results' => $results]);
    }
}

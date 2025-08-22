<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SimulatedTrade;
use App\Services\SettingsService as Cfg;

class BacktestController extends Controller
{
    public function index(Request $r)
    {
        $from = $r->input('from');
        $to   = $r->input('to');
        $ticker = $r->input('ticker');

        $q = SimulatedTrade::query();
        if ($from) $q->whereDate('date','>=',$from);
        if ($to)   $q->whereDate('date','<=',$to);
        if ($ticker) $q->where('ticker', strtoupper($ticker));

        $rowsE = $q->orderBy('date','desc')->limit(500)->get();
        $rows = $rowsE->map(function($t){
            return [
                'ticker' => $t->ticker,
                'entry'  => $t->entry_price,
                'sl'     => $t->sl_price,
                'tp1'    => $t->tp1,
                'tp2'    => $t->tp2,
                'exit'   => $t->exit_price,
                'status' => $t->status,
                'reason' => $t->reason ?? null,
            ];
        })->toArray();

        $wins = $rowsE->whereIn('status',['TP1','TP2'])->count();
        $closed = $rowsE->whereIn('status',['TP1','TP2','SL','closed'])->count();
        $avgR = $rowsE->avg('r_multiple') ?? 0.0;
        $net = $rowsE->sum('net_profit');

        $kpi = [
            'trades'   => $closed,
            'win_rate' => $closed ? ($wins/$closed*100.0) : 0.0,
            'avg_r'    => $avgR,
            'net'      => $net,
        ];

        $currency = Cfg::get('CURRENCY','kr');

        return view('backtest', compact('rows','kpi','currency'));
    }
}

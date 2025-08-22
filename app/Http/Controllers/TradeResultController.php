<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SimulatedTrade;
use App\Services\SettingsService as Cfg;

class TradeResultController extends Controller
{
    public function index(Request $r)
    {
        $q = SimulatedTrade::query();

        if ($r->filled('date')) $q->whereDate('date', $r->input('date'));
        if ($r->filled('ticker')) $q->where('ticker', strtoupper($r->input('ticker')));
        if ($r->filled('status')) $q->where('status', $r->input('status'));

        $trades = $q->orderBy('date','desc')->orderBy('created_at','desc')->paginate(50);

        $scope = clone $q;
        $rowsAll = $scope->get();
        $closed = $rowsAll->whereIn('status',['TP1','TP2','SL','closed'])->count();
        $wins   = $rowsAll->whereIn('status',['TP1','TP2'])->count();
        $winRate = $closed ? ($wins/$closed*100.0) : 0.0;
        $avgR    = $rowsAll->avg('r_multiple') ?? 0.0;
        $fees    = $rowsAll->sum('fees');
        $net     = $rowsAll->sum('net_profit');

        $kpi = [
            'closed'   => $closed,
            'win_rate' => $winRate,
            'avg_r'    => $avgR,
            'fees'     => $fees,
            'net'      => $net,
        ];

        $currency = Cfg::get('CURRENCY','kr');

        if ($r->boolean('export')) {
            $csv = "date,ticker,status,entry,sl,exit,fees,net\n";
            foreach ($rowsAll as $t) {
                $csv .= sprintf(
                    "%s,%s,%s,%s,%s,%s,%s,%s\n",
                    $t->date, $t->ticker, $t->status,
                    $t->entry_price, $t->sl_price, $t->exit_price,
                    $t->fees, $t->net_profit
                );
            }
            return response($csv,200,[
                'Content-Type'=>'text/csv',
                'Content-Disposition'=>'attachment; filename=\"results.csv\"'
            ]);
        }

        return view('results', compact('trades','kpi','currency'));
    }
}

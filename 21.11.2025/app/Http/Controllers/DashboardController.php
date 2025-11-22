<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SimulatedTrade;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $to   = Carbon::today();
        $from = Carbon::today()->subWeekday(7);

        $today = Carbon::today()->toDateString();
        $tradesToday = SimulatedTrade::whereDate('date', $today)->get();
        $closedToday = $tradesToday->whereIn('status', ['closed','SL','TP1','TP2']);
        $openToday   = $tradesToday->where('status', 'open');

        $scope = SimulatedTrade::whereDate('date', '>=', $from)
                 ->whereDate('date', '<=', $to)
                 ->get();

        $closed = $scope->filter(function($t){
            return in_array($t->status, ['closed','SL','TP1','TP2'])
                && is_numeric($t->entry_price) && is_numeric($t->sl_price)
                && is_numeric($t->exit_price)  && ($t->entry_price > $t->sl_price);
        });

        $wins = $closed->filter(fn($t) => $t->exit_price > $t->entry_price)->count();
        $tradeCount = $closed->count();
        $winRate5d = $tradeCount ? round(100 * $wins / $tradeCount, 1) : 0.0;

        $net5d  = round($scope->sum(fn($t) => (float)($t->net_profit ?? 0)), 2);
        $fees5d = round($scope->sum(fn($t) => (float)($t->fees ?? 0)), 2);

        $gains  = $scope->sum(function($t){ $x = (float)($t->net_profit ?? 0); return $x > 0 ? $x : 0; });
        $losses = $scope->sum(function($t){ $x = (float)($t->net_profit ?? 0); return $x < 0 ? abs($x) : 0; });
        $profitFactor5d = $losses > 0 ? round($gains / $losses, 2) : ($gains > 0 ? INF : 0);

        $avgR5d = $tradeCount ? round($closed->avg(function($t){
            $risk = $t->entry_price - $t->sl_price;
            if ($risk <= 0) return null;
            return ($t->exit_price - $t->entry_price) / $risk;
        }), 3) : 0.0;

        $tpHits = $closed->whereIn('status', ['TP1','TP2'])->count();
        $slHits = $closed->where('status', 'SL')->count();
        $tpRate5d = $tradeCount ? round(100 * $tpHits / $tradeCount, 1) : 0.0;
        $slRate5d = $tradeCount ? round(100 * $slHits / $tradeCount, 1) : 0.0;

        $best  = $scope->sortByDesc('net_profit')->first();
        $worst = $scope->sortBy('net_profit')->first();

        $recent = SimulatedTrade::orderBy('created_at','desc')->limit(10)->get();

        $perTicker = $closed->groupBy('ticker')->map(function($g){
            $wins = $g->filter(fn($t)=>$t->exit_price > $t->entry_price)->count();
            $cnt  = $g->count();
            $net  = $g->sum('net_profit');
            return [
                'trades'  => $cnt,
                'winRate' => $cnt ? round(100*$wins/$cnt,1) : 0,
                'net'     => round($net,2),
            ];
        })->sortByDesc('net')->take(6);

        return view('dashboard', [
            'todayTrades'    => $closedToday->count(),
            'openTrades'     => $openToday->count(),
            'winRate5d'      => $winRate5d,
            'net5d'          => $net5d,
            'fees5d'         => $fees5d,
            'profitFactor5d' => $profitFactor5d,
            'avgR5d'         => $avgR5d,
            'tpRate5d'       => $tpRate5d,
            'slRate5d'       => $slRate5d,
            'best'           => $best,
            'worst'          => $worst,
            'recent'         => $recent,
            'perTicker'      => $perTicker,
            'from'           => $from->toDateString(),
            'to'             => $to->toDateString(),
        ]);
    }
}

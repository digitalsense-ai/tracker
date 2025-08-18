<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KpiController extends Controller {
    public function index(Request $request) {
        $from=$request->input('from'); $to=$request->input('to'); $ticker=$request->input('ticker');
        $q=DB::table('simulated_trades'); if($from)$q->where('date','>=',$from); if($to)$q->where('date','<=',$to); if($ticker)$q->where('ticker',$ticker);
        $rows=$q->orderBy('date','desc')->limit(5000)->get();
        $count=$rows->count(); $wins=$rows->whereIn('status',['TP1','TP2','TP3'])->count(); $loss=$rows->whereIn('status',['SL','TSL'])->count(); $closed=$rows->where('status','closed')->count();
        $sumNet=$rows->sum(fn($r)=>(float)($r->net_profit ?? 0));
        $sumR=0; $rCount=0; foreach($rows as $r){ $entry=(float)($r->entry_price ?? 0); $sl=(float)($r->sl_price ?? 0); $exit=(float)($r->exit_price ?? 0);
            if($entry>0 && $sl>0){ $risk=max(0.0001,$entry-$sl); $sumR += ($exit-$entry)/$risk; $rCount++; } }
        $avgR=$rCount ? round($sumR/$rCount,3) : null; $winrate=$count ? round(100.0*$wins/$count,2) : null;
        return view('kpi', compact('from','to','ticker','count','wins','loss','closed','winrate','avgR','sumNet','rows'));
    }
}
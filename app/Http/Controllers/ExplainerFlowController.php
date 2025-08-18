<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExplainerFlowController extends Controller {
    public function index(Request $request){
        $ticker=$request->input('ticker'); $date=$request->input('date');
        $q=DB::table('simulated_trades'); if($ticker)$q->where('ticker',$ticker); if($date)$q->where('date',$date);
        $trade=$q->orderBy('created_at','desc')->first();
        $flow=[
            ['step'=>'Data Loaded','detail'=>'Minute candles fetched (Yahoo/Finnhub)','status'=>$trade?'pass':'unknown'],
            ['step'=>'ORB Computed','detail'=>'Opening range from first N minutes','status'=>'pass'],
            ['step'=>'Breakout','detail'=>'Close > ORB high + buffer','status'=>'pass'],
            ['step'=>'Retest','detail'=>'Low <= ORB high','status'=>'pass'],
            ['step'=>'Confirm','detail'=>'Close > breakout level (entry)','status'=>'pass'],
            ['step'=>'TP/SL Progress','detail'=>'TP1/TP2 or SL/TSL checks','status'=>$trade ? ($trade->status ?? '-') : '-'],
            ['step'=>'Exit','detail'=>'Exit at TP/SL or end-of-session','status'=>$trade ? 'done' : 'pending'],
            ['step'=>'P/L','detail'=>'Net profit after fees','status'=>$trade ? ($trade->net_profit ?? '-') : '-'],
        ];
        return view('explainer_flow', compact('ticker','date','trade','flow'));
    }
}
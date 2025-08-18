<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatusController extends Controller {
    public function index() {
        $strategy = [
            'range_minutes'=>config('strategy.range_minutes',null),
            'entry_buffer_percent'=>config('strategy.entry_buffer_percent',null),
            'require_retest'=>config('strategy.require_retest',null),
            'sl_buffer_percent'=>config('strategy.sl_buffer_percent',null),
            'tp_levels'=>config('strategy.tp_levels',[]),
            'enable_trailing_stop'=>config('strategy.enable_trailing_stop',null),
            'session_start'=>config('strategy.session_start',null),
            'session_end'=>config('strategy.session_end',null),
            'position_usd'=>config('strategy.position_usd',null),
            'fee_percent'=>config('strategy.fee_percent',null),
            'fee_min_per_order'=>config('strategy.fee_min_per_order',null),
            'datafeed'=>config('strategy.datafeed','yahoo'),
            'yahoo_suffixes'=>config('strategy.yahoo_suffixes',[]),
        ];
        $lastTrade = DB::table('simulated_trades')->orderBy('created_at','desc')->first();
        $lastRunAt = $lastTrade ? Carbon::parse($lastTrade->created_at)->setTimezone('America/New_York') : null;
        $health = [
            'datafeed'=>['name'=>strtoupper($strategy['datafeed'] ?? 'N/A'),'status'=>$lastTrade?'OK':'UNKNOWN','note'=>$lastTrade?'Trades exist in DB.':'No trades found yet.'],
            'cron'=>['status'=>$lastRunAt?'OK':'UNKNOWN','last_run_ny'=>$lastRunAt ? $lastRunAt->toDateTimeString() : '-'],
            'broker'=>['name'=>'Nordnet (planned)','status'=>'NOT_CONFIGURED','note'=>'Wire up when ready.'],
        ];
        return view('status', compact('strategy','health'));
    }
}
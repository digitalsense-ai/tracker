<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BacktestController extends Controller
{
    public function index()
    {
        // Prefer real trades if model exists, else fallback to demo data
        $rows = [];
        if (class_exists(\App\Models\SimulatedTrade::class)) {
            $rows = \App\Models\SimulatedTrade::query()
                ->orderBy('date','desc')->limit(200)
                ->get(['date','ticker','status','entry_price','sl_price','exit_price','qty','fees_bps','side'])
                ->map(function($t){
                    return [
                        'date'=>(string)$t->date,
                        'ticker'=>$t->ticker,
                        'status'=>$t->status,
                        'entry_price'=>(float)$t->entry_price,
                        'sl_price'=>$t->sl_price !== null ? (float)$t->sl_price : null,
                        'exit_price'=>$t->exit_price !== null ? (float)$t->exit_price : null,
                        'qty'=>$t->qty ?? 1,
                        'fees_bps'=>$t->fees_bps ?? 0,
                        'side'=>$t->side ?? 'long',
                    ];
                })->toArray();
        }

        if (!$rows) {
            $rows = [
                ['date'=>date('Y-m-d'),'ticker'=>'AAPL','side'=>'long','entry_price'=>100,'exit_price'=>110,'sl_price'=>98,'qty'=>10,'fees_bps'=>1.5],
                ['date'=>date('Y-m-d'),'ticker'=>'MSFT','side'=>'short','entry_price'=>200,'exit_price'=>195,'sl_price'=>205,'qty'=>5,'fees_bps'=>1.0],
            ];
        }

        return view('backtest', ['results'=>$rows]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SignalsController extends Controller
{
    /**
     * Returns JSON list of signals for a given date.
     * Source priority:
     * 1) SimulatedTrade table, if model exists
     * 2) BacktestService::simulateForDate (slow fallback)
     */
    public function index(Request $r)
    {
        $date = $r->input('date'); // YYYY-MM-DD optional
        $pretty = $r->boolean('pretty', false);

        $rows = [];

        // 1) DB model if available
        if (class_exists(\App\Models\SimulatedTrade::class)) {
            try {
                $q = \App\Models\SimulatedTrade::query();
                if ($date) $q->whereDate('date', $date);
                $rows = $q->orderBy('date','desc')->limit(1000)->get(['date','ticker','status','entry_price','sl_price','exit_price','net_profit'])->map(function($t){
                    return [
                        'date' => (string)$t->date,
                        'ticker' => $t->ticker,
                        'status' => $t->status,
                        'entry_price' => (float)$t->entry_price,
                        'sl_price' => $t->sl_price !== null ? (float)$t->sl_price : null,
                        'exit_price' => $t->exit_price !== null ? (float)$t->exit_price : null,
                        'net_profit' => (float)$t->net_profit,
                    ];
                })->toArray();
            } catch (\Throwable $e) {
                Log::warning('Signals DB load failed: '.$e->getMessage());
            }
        }

        // 2) Fallback to service if empty
        if (!$rows && class_exists(\App\Services\BacktestService::class)) {
            try {
                $svc = app(\App\Services\BacktestService::class);
                if (method_exists($svc, 'simulateForDate')) {
                    $res = $svc->simulateForDate(now()->startOfDay(), 1);
                    if (is_array($res)) $rows = $res;
                }
            } catch (\Throwable $e) {
                Log::warning('Signals service fallback failed: '.$e->getMessage());
            }
        }

        $payload = ['status'=>'ok','count'=>count($rows),'signals'=>$rows];

        if ($pretty) {
            // Render simple blade as pretty text (like your toggle)
            return response()->view('signals_pretty', ['json'=>json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)]);
        }
        return response()->json($payload);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Config;

class StatusController extends Controller
{
    public function index()
    {
        return view('status', [
            'strategy' => [
                'range_minutes' => config('strategy.range_minutes'),
                'entry_buffer_percent' => config('strategy.entry_buffer_percent'),
                'require_retest' => config('strategy.require_retest'),
                'sl_buffer_percent' => config('strategy.sl_buffer_percent'),
                'take_profit_rr' => config('strategy.take_profit_rr'),
                'enable_trailing_stop' => config('strategy.enable_trailing_stop'),
                'risk_per_trade' => config('strategy.risk_per_trade'),
                'max_trades_per_day' => config('strategy.max_trades_per_day'),
                'session_start' => config('strategy.session_start'),
                'session_end' => config('strategy.session_end'),
                'min_atr' => config('strategy.min_atr'),
            ],
        ]);
    }
}

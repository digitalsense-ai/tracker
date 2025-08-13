<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ORB / Retest Strategy Parameters
    |--------------------------------------------------------------------------
    */

    // Opening range length (minutes)
    'range_minutes' => 15,

    // Entry condition: must close above ORB high by this buffer (percent). 0.00 = none
    'entry_buffer_percent' => 0.00,

    // Require a retest of ORB high before entry?
    'require_retest' => true,

    // Stop Loss buffer (percent) applied to ORB low (use 0 for none)
    'sl_buffer_percent' => 0.00,

    // Take Profit levels as multiples of R (R = entry - SL). You can add/remove levels.
    'tp_levels' => [
        1.0, // TP1 = 1R
        2.0, // TP2 = 2R
    ],

    // Trailing stop: when first TP is hit, move SL to entry and continue seeking higher TPs
    'enable_trailing_stop' => false,

    // Max number of trades per ticker per day
    'max_trades_per_ticker' => 1,

    // Session window (New York time). Example '09:30' to '12:00'
    'session_start' => '09:30',
    'session_end'   => '12:00',

    // ATR filter (minimum ATR to trade). null disables it.
    'min_atr' => null,


    /*
    |--------------------------------------------------------------------------
    | System Parameters
    |--------------------------------------------------------------------------
    */

    // Which ticker list to load (if applicable)
    'ticker_list' => 'nordnet_tickers.json',

    // Fee model (percent of notional per trade). Used elsewhere when computing P/L.
    'fee_percent' => 0.10, // 0.10%

    // Execution delay in seconds (paper/live engines can apply this).
    'execution_delay_sec' => 0,

    // Datafeed source hint
    'datafeed' => 'yahoo', // or 'finnhub'

];

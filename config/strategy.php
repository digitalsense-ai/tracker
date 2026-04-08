<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Core Engine Settings
    |--------------------------------------------------------------------------
    */

    // Opening/setup range length in minutes
    'range_minutes' => 15,

    // Price must move this percent beyond the breakout level before entry is valid
    'entry_buffer_percent' => 0.00,

    // Require price to retest the breakout level before entry
    'require_retest' => true,

    // Extra percent buffer applied when placing the stop-loss
    'sl_buffer_percent' => 0.00,

    // Core profit model: target expressed as risk/reward multiple
    'take_profit_rr' => 2,

    // If true, the target becomes a trigger for runner mode instead of a final exit
    'enable_trailing_stop' => false,

    // Session window (New York time)
    'session_start' => '09:30',
    'session_end'   => '12:00',

    // Minimum ATR required to allow trading. null disables the filter.
    'min_atr' => null,

    // Risk management
    'risk_per_trade' => 0.01,
    'max_trades_per_ticker' => 1,
    'max_trades_per_day' => 3,

    /*
    |--------------------------------------------------------------------------
    | Simulation / Execution Settings
    |--------------------------------------------------------------------------
    */

    // Fee model (percent of notional per trade)
    'fee_percent' => 0.10,

    // Minimum broker fee charged per order
    'fee_min_per_order' => 0.00,

    // Execution delay in seconds (paper/live engines can apply this)
    'execution_delay_sec' => 0,

    /*
    |--------------------------------------------------------------------------
    | Market Data / Universe Settings
    |--------------------------------------------------------------------------
    */

    // Market data source hint
    'datafeed' => 'yahoo', // or 'finnhub'

    // Which ticker list to load
    'ticker_list' => 'nordnet_tickers.json',

    // Optional provider-specific symbol suffix rules
    'yahoo_suffixes' => [],

    /*
    |--------------------------------------------------------------------------
    | Optional Advanced Settings
    |--------------------------------------------------------------------------
    */

    // Optional ATR-based stop multiplier for advanced stop logic
    'stop_loss_atr_multiplier' => 1.5,
];

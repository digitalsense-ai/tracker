<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trading Source Registry
    |--------------------------------------------------------------------------
    |
    | Central configuration for external sources used by the trading and AI
    | infrastructure. Initial rollout is metadata-only and observe-only.
    |
    */

    'default_mode' => env('TRADING_SOURCE_MODE', 'simulation'),

    'market_data' => [
        'primary' => env('MARKET_DATA_PROVIDER', 'simulation'),
        'fallback' => env('MARKET_DATA_FALLBACK_PROVIDER', null),
    ],

    'news' => [
        'primary' => env('NEWS_PROVIDER', 'manual'),
        'fallback' => env('NEWS_FALLBACK_PROVIDER', null),
    ],

    'ai' => [
        'primary' => env('AI_PROVIDER', 'openai'),
        'fallback' => env('AI_FALLBACK_PROVIDER', null),
    ],

    'execution' => [
        'primary' => env('EXECUTION_PROVIDER', 'paper'),
        'fallback' => env('EXECUTION_FALLBACK_PROVIDER', null),
    ],
];

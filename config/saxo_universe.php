<?php

return [
    // What instruments we consider for the "full universe" scanner
    'asset_types' => array_filter(array_map('trim', explode(',', env('SAXO_UNIVERSE_ASSET_TYPES', 'Stock')))),
    'exchange_ids'=> array_filter(array_map('trim', explode(',', env('SAXO_UNIVERSE_EXCHANGE_IDS', 'NASDAQ,NYSE')))),

    // Safety caps
    'sync' => [
        'page_size' => (int) env('SAXO_UNIVERSE_SYNC_TOP', 200),
        'max_rows'  => (int) env('SAXO_UNIVERSE_SYNC_MAX', 2000),
    ],
];

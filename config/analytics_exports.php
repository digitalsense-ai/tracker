<?php

return [
    'enabled' => env('ANALYTICS_EXPORTS_ENABLED', false),
    'default_format' => env('ANALYTICS_EXPORT_FORMAT', 'json'),
    'export_path' => env('ANALYTICS_EXPORT_PATH', 'analytics'),

    'include' => [
        'trades' => true,
        'ai_decisions' => true,
        'confidence_snapshots' => true,
        'regime_snapshots' => true,
        'execution_quality' => true,
        'portfolio_risk' => true,
    ],

    'read_only' => true,
    'sanitize_account_identifiers' => true,
];

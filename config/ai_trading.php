<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Adaptive AI Trading Infrastructure
    |--------------------------------------------------------------------------
    |
    | Foundation configuration for the adaptive AI architecture.
    | The default rollout mode is observe_only.
    |
    */

    'default_activation_mode' => 'observe_only',

    'modules' => [
        'reality_check' => [
            'enabled' => true,
            'activation_mode' => 'observe_only',
        ],

        'confidence_engine' => [
            'enabled' => true,
            'activation_mode' => 'observe_only',
        ],

        'market_regime' => [
            'enabled' => true,
            'activation_mode' => 'observe_only',
        ],

        'portfolio_risk' => [
            'enabled' => false,
            'activation_mode' => 'observe_only',
        ],

        'meta_ai_supervisor' => [
            'enabled' => false,
            'activation_mode' => 'observe_only',
        ],
    ],

    'logging' => [
        'store_reason_codes' => true,
        'store_input_summary' => true,
    ],
];

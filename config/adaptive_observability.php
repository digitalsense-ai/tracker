<?php

return [
    'enabled' => env('ADAPTIVE_OBSERVABILITY_ENABLED', true),

    'trace_decisions' => true,
    'trace_modules' => true,
    'trace_timing' => true,
    'trace_health' => true,

    'thresholds' => [
        'slow_cycle_ms' => 1000,
        'degraded_health' => 70,
        'critical_health' => 40,
    ],
];

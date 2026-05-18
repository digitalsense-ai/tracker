<?php

namespace App\Services\AI;

class StrategySelectorAiService
{
    /**
     * Foundation strategy selector service.
     *
     * Future versions may recommend:
     * - preferred strategies
     * - strategy weighting
     * - strategy deactivation
     * - strategy confidence adjustments
     * based on regime, volatility, news, and performance.
     *
     * Initial implementation is observe-only.
     */
    public function select(array $context = []): array
    {
        return [
            'preferred_strategies' => [],
            'reason_codes' => ['foundation_version'],
        ];
    }
}

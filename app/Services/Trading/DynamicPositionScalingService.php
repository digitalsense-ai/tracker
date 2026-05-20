<?php

namespace App\Services\Trading;

class DynamicPositionScalingService
{
    /**
     * Foundation dynamic position scaling service.
     *
     * Future versions may recommend:
     * - scale up
     * - scale down
     * - hold size
     * based on confidence, regime, execution quality,
     * and portfolio heat.
     *
     * Initial implementation is observe-only.
     */
    public function evaluate(array $context = []): array
    {
        return [
            'action' => 'hold',
            'reason_codes' => ['foundation_version'],
        ];
    }
}

<?php

namespace App\Services\Risk;

class CapitalAllocationService
{
    /**
     * Foundation capital allocation service.
     *
     * Future versions may recommend:
     * - capital distribution
     * - strategy weighting
     * - exposure reduction
     * - volatility-adjusted sizing
     * - portfolio balancing
     *
     * Initial implementation is observe-only.
     */
    public function allocate(array $context = []): array
    {
        return [
            'allocations' => [],
            'reason_codes' => ['foundation_version'],
        ];
    }
}

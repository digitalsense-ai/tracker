<?php

namespace App\Services\Risk;

class OpportunityCostService
{
    /**
     * Foundation opportunity cost service.
     *
     * Future versions may evaluate:
     * - capital efficiency
     * - alternative setup quality
     * - weak capital utilization
     * - portfolio crowding
     *
     * Initial implementation is informational only.
     */
    public function evaluate(array $context = []): array
    {
        return [
            'score' => 0,
            'reason_codes' => ['foundation_version'],
        ];
    }
}

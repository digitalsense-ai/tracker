<?php

namespace App\Services\Risk;

use App\DTO\AI\RiskGuardResult;

class RiskGuardService
{
    /**
     * Foundation risk guard service.
     *
     * Hard risk rules must always override AI recommendations.
     * Initial implementation is observe-only.
     */
    public function evaluate(array $context = []): RiskGuardResult
    {
        return new RiskGuardResult(
            approved: true,
            decision: 'allow',
            blockedBy: [],
            warnings: [],
            reasonCodes: ['foundation_version'],
        );
    }
}

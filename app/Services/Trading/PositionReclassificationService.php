<?php

namespace App\Services\Trading;

use App\DTO\AI\PositionReclassificationResult;

class PositionReclassificationService
{
    /**
     * Foundation position reclassification service.
     *
     * Future versions may determine whether an existing trade
     * now fits a stronger compatible strategy thesis.
     *
     * Initial implementation is observe-only.
     */
    public function evaluate(array $context = []): PositionReclassificationResult
    {
        return new PositionReclassificationResult(
            canReclassify: false,
            fromStrategy: null,
            toStrategy: null,
            confidence: 0,
            reasonCodes: ['foundation_version'],
            safetyChecks: [],
        );
    }
}

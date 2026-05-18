<?php

namespace App\Services\AI;

use App\DTO\AI\ConfidenceResult;

class ConfidenceEngineService
{
    /**
     * Foundation confidence engine.
     *
     * Initial implementation is intentionally conservative and
     * observe-only. Later versions may use:
     * - setup quality
     * - market regime fit
     * - spread/liquidity
     * - news risk
     * - historical strategy performance
     * - portfolio heat
     */
    public function calculate(array $context = []): ConfidenceResult
    {
        return new ConfidenceResult(
            confidence: 50,
            uncertainty: 50,
            level: 'neutral',
            reasonCodes: ['foundation_version'],
        );
    }
}

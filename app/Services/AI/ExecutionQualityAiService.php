<?php

namespace App\Services\AI;

use App\DTO\AI\ExecutionQualityResult;

class ExecutionQualityAiService
{
    /**
     * Foundation execution quality service.
     *
     * Future versions may evaluate:
     * - slippage
     * - spread quality
     * - timing quality
     * - fill efficiency
     * - execution degradation
     *
     * Initial implementation is observe-only.
     */
    public function evaluate(array $context = []): ExecutionQualityResult
    {
        return new ExecutionQualityResult(
            qualityScore: 100,
            expectedEntry: null,
            actualEntry: null,
            slippage: null,
            spreadAtEntry: null,
            reasonCodes: ['foundation_version'],
        );
    }
}

<?php

namespace App\Services\AI;

use App\DTO\AI\RealityCheckResult;
use App\Enums\AI\AiRecommendedAction;

class AiRealityCheckService
{
    /**
     * Observe-only foundation version.
     *
     * This service currently returns recommendations only.
     * No live trading behavior should be modified by this class.
     */
    public function evaluate(array $context = []): RealityCheckResult
    {
        return new RealityCheckResult(
            planStillValid: true,
            setupStillValid: true,
            regimeChanged: false,
            newsRiskChanged: false,
            recommendedAction: AiRecommendedAction::Continue->value,
            reasonCodes: [],
        );
    }
}

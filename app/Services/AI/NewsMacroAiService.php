<?php

namespace App\Services\AI;

use App\DTO\AI\NewsRiskResult;

class NewsMacroAiService
{
    /**
     * Foundation news and macro AI service.
     *
     * Future versions may evaluate:
     * - macro calendar events
     * - earnings risk
     * - breaking headlines
     * - sector-specific news
     * - market-wide event risk
     *
     * Initial implementation is observe-only.
     */
    public function evaluate(array $context = []): NewsRiskResult
    {
        return new NewsRiskResult(
            riskScore: 0,
            riskLevel: 'unknown',
            hasHighImpactEvent: false,
            headlines: [],
            reasonCodes: ['foundation_version'],
        );
    }
}

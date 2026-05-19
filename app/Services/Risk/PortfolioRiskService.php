<?php

namespace App\Services\Risk;

use App\DTO\AI\PortfolioRiskResult;
use App\Enums\AI\PortfolioHeatLevel;

class PortfolioRiskService
{
    /**
     * Foundation portfolio risk service.
     *
     * Initial implementation is observe-only and returns
     * informational portfolio risk state only.
     */
    public function analyze(array $context = []): PortfolioRiskResult
    {
        return new PortfolioRiskResult(
            riskScore: 0,
            heatLevel: PortfolioHeatLevel::Low,
            riskFactors: [],
            recommendations: [],
        );
    }
}

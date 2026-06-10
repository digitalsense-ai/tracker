<?php

namespace App\DTO\AI;

use App\Enums\AI\PortfolioHeatLevel;

readonly class PortfolioRiskResult
{
    public function __construct(
        public int $riskScore,
        public PortfolioHeatLevel $heatLevel,
        public array $riskFactors = [],
        public array $recommendations = [],
    ) {
    }
}

<?php

namespace App\Services\Risk;

use App\Enums\AI\PortfolioHeatLevel;

class PortfolioHeatService
{
    /**
     * Foundation portfolio heat service.
     *
     * Future versions may combine:
     * - volatility
     * - drawdown
     * - correlation
     * - open risk
     * - spread degradation
     * - execution quality
     * - news risk
     *
     * Initial implementation is informational only.
     */
    public function calculate(array $context = []): PortfolioHeatLevel
    {
        return PortfolioHeatLevel::Low;
    }
}

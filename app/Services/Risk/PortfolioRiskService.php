<?php

namespace App\Services\Risk;

use App\DTO\AI\PortfolioRiskResult;
use App\Enums\AI\PortfolioHeatLevel;

class PortfolioRiskService
{
    /**
     * Observe-only portfolio risk service v1.
     *
     * Supported context keys:
     * - open_risk_score: int 0-100
     * - correlation_score: int 0-100
     * - sector_exposure_score: int 0-100
     * - drawdown_score: int 0-100
     * - volatility_score: int 0-100
     * - execution_risk_score: int 0-100
     * - news_risk_score: int 0-100
     */
    public function analyze(array $context = []): PortfolioRiskResult
    {
        $riskFactors = [];
        $recommendations = [];

        $openRisk = $this->score($context, 'open_risk_score');
        $correlation = $this->score($context, 'correlation_score');
        $sectorExposure = $this->score($context, 'sector_exposure_score');
        $drawdown = $this->score($context, 'drawdown_score');
        $volatility = $this->score($context, 'volatility_score');
        $executionRisk = $this->score($context, 'execution_risk_score');
        $newsRisk = $this->score($context, 'news_risk_score');

        $riskScore = (int) round(
            ($openRisk * 0.20)
            + ($correlation * 0.20)
            + ($sectorExposure * 0.15)
            + ($drawdown * 0.15)
            + ($volatility * 0.10)
            + ($executionRisk * 0.10)
            + ($newsRisk * 0.10)
        );

        if ($openRisk >= 70) {
            $riskFactors[] = 'open_risk_high';
        }

        if ($correlation >= 70) {
            $riskFactors[] = 'correlation_risk_high';
        }

        if ($sectorExposure >= 70) {
            $riskFactors[] = 'sector_exposure_high';
        }

        if ($drawdown >= 70) {
            $riskFactors[] = 'drawdown_pressure_high';
        }

        if ($volatility >= 70) {
            $riskFactors[] = 'volatility_risk_high';
        }

        if ($executionRisk >= 70) {
            $riskFactors[] = 'execution_risk_high';
        }

        if ($newsRisk >= 70) {
            $riskFactors[] = 'news_risk_high';
        }

        if ($riskScore >= 80) {
            $recommendations[] = 'portfolio_heat_critical_watch';
            $recommendations[] = 'reduce_new_risk_watch';
        } elseif ($riskScore >= 65) {
            $recommendations[] = 'portfolio_heat_high_watch';
        } elseif ($riskScore >= 40) {
            $recommendations[] = 'portfolio_heat_medium_watch';
        } else {
            $recommendations[] = 'portfolio_heat_low';
        }

        return new PortfolioRiskResult(
            riskScore: $riskScore,
            heatLevel: $this->heatLevel($riskScore),
            riskFactors: $riskFactors,
            recommendations: $recommendations,
        );
    }

    private function score(array $context, string $key): int
    {
        return max(0, min(100, (int) ($context[$key] ?? 0)));
    }

    private function heatLevel(int $riskScore): PortfolioHeatLevel
    {
        return match (true) {
            $riskScore >= 80 => PortfolioHeatLevel::Critical,
            $riskScore >= 65 => PortfolioHeatLevel::High,
            $riskScore >= 40 => PortfolioHeatLevel::Medium,
            default => PortfolioHeatLevel::Low,
        };
    }
}

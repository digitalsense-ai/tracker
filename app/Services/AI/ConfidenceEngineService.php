<?php

namespace App\Services\AI;

use App\DTO\AI\ConfidenceResult;

class ConfidenceEngineService
{
    /**
     * Observe-only confidence engine v1.
     *
     * Supported context keys:
     * - setup_score: int 0-100
     * - regime_fit: int 0-100
     * - liquidity_score: int 0-100
     * - news_risk_score: int 0-100 where higher means more risk
     * - execution_quality: int 0-100
     * - recent_model_score: int 0-100
     */
    public function calculate(array $context = []): ConfidenceResult
    {
        $reasonCodes = [];

        $setupScore = $this->score($context, 'setup_score', 50);
        $regimeFit = $this->score($context, 'regime_fit', 50);
        $liquidityScore = $this->score($context, 'liquidity_score', 50);
        $newsRiskScore = $this->score($context, 'news_risk_score', 50);
        $executionQuality = $this->score($context, 'execution_quality', 50);
        $recentModelScore = $this->score($context, 'recent_model_score', 50);

        $confidence = (int) round(
            ($setupScore * 0.25)
            + ($regimeFit * 0.20)
            + ($liquidityScore * 0.15)
            + ((100 - $newsRiskScore) * 0.15)
            + ($executionQuality * 0.10)
            + ($recentModelScore * 0.15)
        );

        if ($setupScore >= 75) {
            $reasonCodes[] = 'setup_quality_strong';
        } elseif ($setupScore < 50) {
            $reasonCodes[] = 'setup_quality_weak';
        }

        if ($regimeFit >= 75) {
            $reasonCodes[] = 'regime_fit_strong';
        } elseif ($regimeFit < 50) {
            $reasonCodes[] = 'regime_fit_weak';
        }

        if ($liquidityScore < 50) {
            $reasonCodes[] = 'liquidity_weak';
        }

        if ($newsRiskScore >= 70) {
            $reasonCodes[] = 'news_risk_high';
        }

        if ($executionQuality < 50) {
            $reasonCodes[] = 'execution_quality_weak';
        }

        if ($recentModelScore < 50) {
            $reasonCodes[] = 'recent_model_performance_weak';
        }

        if ($reasonCodes === []) {
            $reasonCodes[] = 'balanced_context';
        }

        return new ConfidenceResult(
            confidence: $confidence,
            uncertainty: 100 - $confidence,
            level: $this->level($confidence),
            reasonCodes: $reasonCodes,
        );
    }

    private function score(array $context, string $key, int $default): int
    {
        return max(0, min(100, (int) ($context[$key] ?? $default)));
    }

    private function level(int $confidence): string
    {
        return match (true) {
            $confidence >= 80 => 'high',
            $confidence >= 60 => 'medium',
            $confidence >= 40 => 'neutral',
            default => 'low',
        };
    }
}

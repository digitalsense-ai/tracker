<?php

namespace App\Services\AI;

use App\DTO\AI\MetaAiSupervisorResult;

class MetaAiSupervisorService
{
    /**
     * Observe-only Meta-AI Supervisor v1.
     *
     * Supported context keys:
     * - confidence_health: int 0-100
     * - regime_stability: int 0-100
     * - portfolio_health: int 0-100
     * - execution_health: int 0-100
     * - model_health: int 0-100
     * - module_disagreement_score: int 0-100 where higher means worse
     */
    public function evaluate(array $context = []): MetaAiSupervisorResult
    {
        $warnings = [];
        $recommendations = [];

        $confidenceHealth = $this->score($context, 'confidence_health', 100);
        $regimeStability = $this->score($context, 'regime_stability', 100);
        $portfolioHealth = $this->score($context, 'portfolio_health', 100);
        $executionHealth = $this->score($context, 'execution_health', 100);
        $modelHealth = $this->score($context, 'model_health', 100);
        $moduleDisagreement = $this->score($context, 'module_disagreement_score', 0);

        $systemHealth = (int) round(
            ($confidenceHealth * 0.20)
            + ($regimeStability * 0.15)
            + ($portfolioHealth * 0.20)
            + ($executionHealth * 0.15)
            + ($modelHealth * 0.20)
            + ((100 - $moduleDisagreement) * 0.10)
        );

        if ($confidenceHealth < 60) {
            $warnings[] = 'confidence_health_weak';
            $recommendations[] = 'review_confidence_engine';
        }

        if ($regimeStability < 60) {
            $warnings[] = 'regime_instability';
            $recommendations[] = 'review_regime_state';
        }

        if ($portfolioHealth < 60) {
            $warnings[] = 'portfolio_health_weak';
            $recommendations[] = 'review_portfolio_risk';
        }

        if ($executionHealth < 60) {
            $warnings[] = 'execution_health_weak';
            $recommendations[] = 'review_execution_quality';
        }

        if ($modelHealth < 60) {
            $warnings[] = 'model_health_weak';
            $recommendations[] = 'review_model_drift';
        }

        if ($moduleDisagreement >= 70) {
            $warnings[] = 'ai_module_disagreement_high';
            $recommendations[] = 'force_manual_review_watch';
        }

        if ($systemHealth < 50) {
            $recommendations[] = 'system_health_critical_watch';
        } elseif ($systemHealth < 70) {
            $recommendations[] = 'system_health_degraded_watch';
        }

        return new MetaAiSupervisorResult(
            systemHealth: $systemHealth,
            confidenceHealth: $confidenceHealth,
            requiresAttention: $warnings !== [],
            warnings: array_values(array_unique($warnings)),
            recommendations: array_values(array_unique($recommendations)),
        );
    }

    private function score(array $context, string $key, int $default): int
    {
        return max(0, min(100, (int) ($context[$key] ?? $default)));
    }
}

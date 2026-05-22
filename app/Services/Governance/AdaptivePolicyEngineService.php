<?php

namespace App\Services\Governance;

class AdaptivePolicyEngineService
{
    /**
     * Observe-only adaptive policy engine v1.
     */
    public function evaluate(array $context = []): array
    {
        $recommendations = [];
        $reasonCodes = [];

        $portfolioHeat = $context['portfolio_heat'] ?? 'normal';
        $confidenceDrift = $context['confidence_drift'] ?? 'low';
        $executionDrift = $context['execution_drift'] ?? 'low';

        if ($portfolioHeat === 'critical') {
            $reasonCodes[] = 'portfolio_heat_critical';
        }

        if ($confidenceDrift === 'high') {
            $reasonCodes[] = 'confidence_drift_high';
        }

        if ($executionDrift === 'high') {
            $reasonCodes[] = 'execution_drift_high';
        }

        if (count($reasonCodes) >= 2) {
            $recommendations[] = 'safe_mode_recommended';
            $recommendations[] = 'operator_review_recommended';
        }

        return [
            'policy_status' => empty($recommendations) ? 'stable' : 'governance_attention_required',
            'recommendations' => $recommendations,
            'reason_codes' => $reasonCodes,
            'observe_only' => true,
        ];
    }
}

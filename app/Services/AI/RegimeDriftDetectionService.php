<?php

namespace App\Services\AI;

class RegimeDriftDetectionService
{
    /**
     * Observe-only regime drift detection v1.
     */
    public function analyze(array $context = []): array
    {
        $regimeStability = max(0, min(100, (int) ($context['regime_stability'] ?? 100)));
        $misclassificationRate = max(0, min(100, (int) ($context['misclassification_rate'] ?? 0)));

        $reasonCodes = [];

        if ($regimeStability < 60) {
            $reasonCodes[] = 'regime_instability_detected';
        }

        if ($misclassificationRate >= 30) {
            $reasonCodes[] = 'regime_misclassification_high';
        }

        if ($reasonCodes === []) {
            $reasonCodes[] = 'regime_drift_not_detected';
        }

        return [
            'drift_detected' => $reasonCodes !== ['regime_drift_not_detected'],
            'recommendation' => $reasonCodes !== ['regime_drift_not_detected']
                ? 'review_regime_classification'
                : 'no_regime_drift_action',
            'reason_codes' => $reasonCodes,
        ];
    }
}

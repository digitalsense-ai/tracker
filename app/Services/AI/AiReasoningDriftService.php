<?php

namespace App\Services\AI;

class AiReasoningDriftService
{
    /**
     * Observe-only AI reasoning drift detection v1.
     */
    public function analyze(array $context = []): array
    {
        $warningEffectiveness = max(0, min(100, (int) ($context['warning_effectiveness'] ?? 100)));
        $disagreementScore = max(0, min(100, (int) ($context['disagreement_score'] ?? 0)));
        $reasonCodeInstability = max(0, min(100, (int) ($context['reason_code_instability'] ?? 0)));

        $reasonCodes = [];

        if ($warningEffectiveness < 60) {
            $reasonCodes[] = 'warning_effectiveness_degraded';
        }

        if ($disagreementScore >= 50) {
            $reasonCodes[] = 'ai_disagreement_increasing';
        }

        if ($reasonCodeInstability >= 40) {
            $reasonCodes[] = 'reason_code_instability_detected';
        }

        if ($reasonCodes === []) {
            $reasonCodes[] = 'ai_reasoning_drift_not_detected';
        }

        return [
            'drift_detected' => $reasonCodes !== ['ai_reasoning_drift_not_detected'],
            'recommendation' => $reasonCodes !== ['ai_reasoning_drift_not_detected']
                ? 'review_ai_reasoning_quality'
                : 'no_reasoning_drift_action',
            'reason_codes' => $reasonCodes,
        ];
    }
}

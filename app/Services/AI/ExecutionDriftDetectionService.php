<?php

namespace App\Services\AI;

class ExecutionDriftDetectionService
{
    /**
     * Observe-only execution drift detection v1.
     */
    public function analyze(array $context = []): array
    {
        $baselineSlippage = (float) ($context['baseline_slippage'] ?? 0);
        $recentSlippage = (float) ($context['recent_slippage'] ?? 0);
        $baselineSpread = (float) ($context['baseline_spread'] ?? 0);
        $recentSpread = (float) ($context['recent_spread'] ?? 0);

        $reasonCodes = [];

        if ($recentSlippage > ($baselineSlippage * 1.5) && $baselineSlippage > 0) {
            $reasonCodes[] = 'slippage_drift_detected';
        }

        if ($recentSpread > ($baselineSpread * 1.5) && $baselineSpread > 0) {
            $reasonCodes[] = 'spread_drift_detected';
        }

        if ($reasonCodes === []) {
            $reasonCodes[] = 'execution_drift_not_detected';
        }

        return [
            'drift_detected' => $reasonCodes !== ['execution_drift_not_detected'],
            'recommendation' => $reasonCodes !== ['execution_drift_not_detected']
                ? 'review_execution_quality'
                : 'no_execution_drift_action',
            'reason_codes' => $reasonCodes,
        ];
    }
}

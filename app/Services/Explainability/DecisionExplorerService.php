<?php

namespace App\Services\Explainability;

class DecisionExplorerService
{
    /**
     * Observe-only decision explorer service v1.
     *
     * Normalizes adaptive decision inspection for operators.
     */
    public function inspect(array $decision = []): array
    {
        return [
            'decision_type' => $decision['decision_type'] ?? 'unknown',
            'confidence' => $decision['confidence'] ?? null,
            'reason_codes' => $decision['reason_codes'] ?? [],
            'warnings' => $decision['warnings'] ?? [],
            'governance_state' => $decision['governance_state'] ?? null,
            'drift_state' => $decision['drift_state'] ?? null,
            'timeline_reference' => $decision['timeline_reference'] ?? null,
            'captured_at' => now()->toIso8601String(),
            'observe_only' => true,
        ];
    }
}

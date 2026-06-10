<?php

namespace App\Services\Observability;

class DecisionTraceService
{
    /**
     * Observe-only decision tracing service v1.
     */
    public function trace(array $decision = []): array
    {
        return [
            'trace_id' => uniqid('trace_', true),
            'captured_at' => now()->toIso8601String(),
            'decision_type' => $decision['decision_type'] ?? 'unknown',
            'confidence' => $decision['confidence'] ?? null,
            'reason_codes' => $decision['reason_codes'] ?? [],
            'warnings' => $decision['warnings'] ?? [],
            'governance_state' => $decision['governance_state'] ?? null,
            'observe_only' => true,
        ];
    }
}

<?php

namespace App\Services\Explainability;

class ExplainabilityFormatterService
{
    /**
     * Observe-only explainability formatter service v1.
     *
     * Converts adaptive reasoning structures into dashboard-friendly output.
     */
    public function format(array $payload = []): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'summary' => $payload['summary'] ?? 'No summary available.',
            'decision_type' => $payload['decision_type'] ?? 'unknown',
            'confidence' => $payload['confidence'] ?? null,
            'reason_codes' => array_values($payload['reason_codes'] ?? []),
            'warnings' => array_values($payload['warnings'] ?? []),
            'governance_state' => $payload['governance_state'] ?? null,
            'read_only' => true,
        ];
    }
}

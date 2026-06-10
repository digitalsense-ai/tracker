<?php

namespace App\Services\Explainability;

class DriftExplorerService
{
    /**
     * Observe-only drift explorer service v1.
     */
    public function inspect(array $drift = []): array
    {
        return [
            'drift_type' => $drift['drift_type'] ?? 'unknown',
            'severity' => $drift['severity'] ?? 'minor',
            'affected_systems' => $drift['affected_systems'] ?? [],
            'reason_codes' => $drift['reason_codes'] ?? [],
            'detected_at' => now()->toIso8601String(),
            'observe_only' => true,
        ];
    }
}

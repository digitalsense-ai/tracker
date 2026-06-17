<?php

namespace App\Services\Explainability;

class GovernanceExplorerService
{
    /**
     * Observe-only governance explorer service v1.
     */
    public function inspect(array $governance = []): array
    {
        return [
            'policy' => $governance['policy'] ?? 'unknown',
            'decision' => $governance['decision'] ?? null,
            'escalation' => $governance['escalation'] ?? null,
            'safe_mode' => $governance['safe_mode'] ?? false,
            'reason_codes' => $governance['reason_codes'] ?? [],
            'captured_at' => now()->toIso8601String(),
            'observe_only' => true,
        ];
    }
}

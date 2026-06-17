<?php

namespace App\Services\Governance;

class GovernanceTimelineService
{
    /**
     * Observe-only governance timeline service v1.
     */
    public function event(array $event = []): array
    {
        return [
            'event_type' => $event['event_type'] ?? 'policy_evaluation',
            'recommendations' => $event['recommendations'] ?? [],
            'reason_codes' => $event['reason_codes'] ?? [],
            'captured_at' => now()->toIso8601String(),
            'observe_only' => true,
        ];
    }
}

<?php

namespace App\Services\Governance;

class RecommendationPriorityService
{
    /**
     * Observe-only recommendation priority service v1.
     */
    public function rank(array $recommendation = []): array
    {
        $severity = (int) ($recommendation['severity'] ?? 0);
        $impact = (int) ($recommendation['impact'] ?? 0);
        $confidence = (int) ($recommendation['confidence'] ?? 0);
        $urgency = (int) ($recommendation['urgency'] ?? 0);

        $priorityScore = $severity + $impact + $confidence + $urgency;

        return [
            'priority_score' => $priorityScore,
            'priority_level' => match (true) {
                $priorityScore >= 300 => 'critical',
                $priorityScore >= 200 => 'high',
                $priorityScore >= 100 => 'medium',
                default => 'low',
            },
            'observe_only' => true,
        ];
    }
}

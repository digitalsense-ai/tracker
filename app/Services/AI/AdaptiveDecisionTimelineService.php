<?php

namespace App\Services\AI;

class AdaptiveDecisionTimelineService
{
    /**
     * Observe-only adaptive decision timeline service v1.
     *
     * Converts adaptive snapshots into normalized timeline events.
     */
    public function event(array $snapshot, string $eventType = 'adaptive_snapshot'): array
    {
        return [
            'event_version' => 1,
            'event_type' => $eventType,
            'occurred_at' => $snapshot['captured_at'] ?? now()->toIso8601String(),
            'system_health' => $snapshot['system_health'] ?? null,
            'requires_attention' => $snapshot['requires_attention'] ?? false,
            'portfolio_heat' => $snapshot['portfolio_heat'] ?? null,
            'confidence_level' => $snapshot['confidence_level'] ?? null,
            'trade_state' => $snapshot['trade_state'] ?? null,
            'adaptive_exit_recommendation' => $snapshot['adaptive_exit_recommendation'] ?? null,
            'reasoning' => $snapshot['reasoning'] ?? [],
        ];
    }

    /**
     * Build a normalized ordered timeline from snapshots.
     */
    public function timeline(array $snapshots): array
    {
        $events = array_map(
            fn (array $snapshot): array => $this->event($snapshot),
            $snapshots,
        );

        usort($events, fn (array $a, array $b): int => strcmp($a['occurred_at'], $b['occurred_at']));

        return $events;
    }
}

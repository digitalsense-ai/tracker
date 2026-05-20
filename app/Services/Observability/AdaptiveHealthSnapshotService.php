<?php

namespace App\Services\Observability;

class AdaptiveHealthSnapshotService
{
    /**
     * Observe-only adaptive health snapshot service v1.
     */
    public function snapshot(array $context = []): array
    {
        $health = max(0, min(100, (int) ($context['health'] ?? 100)));

        return [
            'captured_at' => now()->toIso8601String(),
            'health' => $health,
            'status' => $this->status($health),
            'module' => $context['module'] ?? 'unknown',
            'cycle_ms' => (int) ($context['cycle_ms'] ?? 0),
            'warnings' => $context['warnings'] ?? [],
            'observe_only' => true,
        ];
    }

    private function status(int $health): string
    {
        return match (true) {
            $health <= config('adaptive_observability.thresholds.critical_health', 40) => 'critical',
            $health <= config('adaptive_observability.thresholds.degraded_health', 70) => 'degraded',
            default => 'healthy',
        };
    }
}

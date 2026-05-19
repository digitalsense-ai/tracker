<?php

namespace App\Services\AI;

class AdaptiveStateDiffService
{
    /**
     * Observe-only adaptive state diff service v1.
     *
     * Detects explainable changes between two adaptive snapshots.
     */
    public function diff(array $previous, array $current): array
    {
        $changes = [];

        foreach ([
            'system_health',
            'portfolio_heat',
            'confidence_level',
            'trade_state',
            'adaptive_exit_recommendation',
        ] as $field) {
            $old = $previous[$field] ?? null;
            $new = $current[$field] ?? null;

            if ($old !== $new) {
                $changes[$field] = [
                    'from' => $old,
                    'to' => $new,
                    'severity' => $this->severity($field, $old, $new),
                ];
            }
        }

        return [
            'has_changes' => $changes !== [],
            'change_count' => count($changes),
            'changes' => $changes,
        ];
    }

    private function severity(string $field, mixed $old, mixed $new): string
    {
        if ($field === 'portfolio_heat' && in_array($new, ['high', 'critical'], true)) {
            return 'critical';
        }

        if ($field === 'system_health' && is_numeric($old) && is_numeric($new)) {
            $delta = (int) $old - (int) $new;

            if ($delta >= 30) {
                return 'critical';
            }

            if ($delta >= 15) {
                return 'moderate';
            }
        }

        return 'minor';
    }
}

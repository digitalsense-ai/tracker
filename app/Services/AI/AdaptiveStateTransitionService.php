<?php

namespace App\Services\AI;

class AdaptiveStateTransitionService
{
    /**
     * Observe-only adaptive state transition service v1.
     *
     * Detects explainable adaptive-state transitions.
     */
    public function transitions(array $previous, array $current): array
    {
        $transitions = [];

        foreach ([
            'portfolio_heat',
            'confidence_level',
            'trade_state',
            'adaptive_exit_recommendation',
        ] as $field) {
            $from = $previous[$field] ?? null;
            $to = $current[$field] ?? null;

            if ($from !== null && $to !== null && $from !== $to) {
                $transitions[] = [
                    'field' => $field,
                    'from' => $from,
                    'to' => $to,
                    'transition_type' => $field.'_transition',
                ];
            }
        }

        return [
            'has_transitions' => $transitions !== [],
            'transition_count' => count($transitions),
            'transitions' => $transitions,
        ];
    }
}

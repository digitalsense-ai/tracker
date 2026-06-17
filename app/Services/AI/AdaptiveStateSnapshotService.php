<?php

namespace App\Services\AI;

class AdaptiveStateSnapshotService
{
    /**
     * Observe-only adaptive state snapshot service v1.
     *
     * Creates normalized explainable snapshots from orchestrated AI state.
     */
    public function snapshot(array $adaptiveState = []): array
    {
        return [
            'snapshot_version' => 1,
            'captured_at' => now()->toIso8601String(),
            'system_health' => data_get($adaptiveState, 'summary.system_health'),
            'requires_attention' => data_get($adaptiveState, 'summary.requires_attention'),
            'portfolio_heat' => data_get($adaptiveState, 'summary.portfolio_heat'),
            'confidence_level' => data_get($adaptiveState, 'summary.confidence_level'),
            'trade_state' => data_get($adaptiveState, 'summary.trade_state'),
            'adaptive_exit_recommendation' => data_get($adaptiveState, 'summary.adaptive_exit_recommendation'),
            'reasoning' => [
                'reality_check' => data_get($adaptiveState, 'adaptive_state.reality_check.reasonCodes', []),
                'confidence' => data_get($adaptiveState, 'adaptive_state.confidence.reasonCodes', []),
                'portfolio_risk' => data_get($adaptiveState, 'adaptive_state.portfolio_risk.riskFactors', []),
                'risk_guard' => data_get($adaptiveState, 'adaptive_state.risk_guard.reasonCodes', []),
                'trade_lifecycle' => data_get($adaptiveState, 'adaptive_state.trade_lifecycle.warnings', []),
                'meta_supervisor' => data_get($adaptiveState, 'adaptive_state.meta_supervisor.warnings', []),
                'execution_quality' => data_get($adaptiveState, 'adaptive_state.execution_quality.reasonCodes', []),
            ],
        ];
    }
}

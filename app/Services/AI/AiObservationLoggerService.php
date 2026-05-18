<?php

namespace App\Services\AI;

use App\Models\AiDecisionLog;

class AiObservationLoggerService
{
    /**
     * Central observe-only AI logging helper.
     *
     * This service standardizes how AI modules store
     * recommendations, confidence, and reasoning.
     */
    public function log(array $payload): AiDecisionLog
    {
        return AiDecisionLog::create([
            'module' => $payload['module'] ?? 'unknown',
            'activation_mode' => $payload['activation_mode'] ?? 'observe_only',
            'subject_type' => $payload['subject_type'] ?? null,
            'subject_id' => $payload['subject_id'] ?? null,
            'recommended_action' => $payload['recommended_action'] ?? null,
            'confidence' => $payload['confidence'] ?? null,
            'uncertainty' => $payload['uncertainty'] ?? null,
            'reason_codes' => $payload['reason_codes'] ?? [],
            'input_summary' => $payload['input_summary'] ?? [],
            'output_payload' => $payload['output_payload'] ?? [],
            'action_executed' => false,
        ]);
    }
}

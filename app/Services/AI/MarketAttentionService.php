<?php

namespace App\Services\AI;

class MarketAttentionService
{
    /**
     * Foundation market attention service.
     *
     * Future versions may track:
     * - unusual market attention
     * - abnormal relative volume
     * - momentum concentration
     * - thematic crowding
     * - social/news focus shifts
     *
     * Initial implementation is informational only.
     */
    public function analyze(array $context = []): array
    {
        return [
            'attention_score' => 0,
            'reason_codes' => ['foundation_version'],
        ];
    }
}

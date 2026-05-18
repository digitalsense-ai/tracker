<?php

namespace App\Services\AI;

class SetupQualityAiService
{
    /**
     * Foundation setup quality service.
     *
     * Future versions may evaluate:
     * - breakout quality
     * - retest structure
     * - continuation strength
     * - fakeout probability
     * - volume confirmation
     * - regime fit
     *
     * Initial implementation is informational only.
     */
    public function evaluate(array $context = []): array
    {
        return [
            'score' => 0,
            'reason_codes' => ['foundation_version'],
        ];
    }
}

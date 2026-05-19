<?php

namespace App\Services\AI;

class PerformanceAttributionAiService
{
    /**
     * Foundation performance attribution service.
     *
     * Future versions may analyze:
     * - setup type
     * - regime fit
     * - news state
     * - confidence quality
     * - time-of-day behavior
     * - execution quality
     * - sector performance
     *
     * Initial implementation is informational only.
     */
    public function analyze(array $context = []): array
    {
        return [
            'status' => 'foundation_version',
            'attribution' => [],
        ];
    }
}

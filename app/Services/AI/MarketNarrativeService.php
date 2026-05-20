<?php

namespace App\Services\AI;

class MarketNarrativeService
{
    /**
     * Foundation market narrative service.
     *
     * Future versions may summarize the current market story:
     * - risk-on/risk-off narrative
     * - sector rotation
     * - headline-driven themes
     * - momentum leadership
     * - defensive behavior
     *
     * Initial implementation is informational only.
     */
    public function summarize(array $context = []): array
    {
        return [
            'narrative' => 'unknown',
            'reason_codes' => ['foundation_version'],
        ];
    }
}

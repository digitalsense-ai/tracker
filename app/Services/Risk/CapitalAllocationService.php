<?php

namespace App\Services\Risk;

class CapitalAllocationService
{
    /**
     * Observe-only capital allocation service v1.
     *
     * Supported context keys:
     * - confidence: int 0-100
     * - thesis_strength: int 0-100
     * - portfolio_heat: low|medium|high|critical
     * - opportunity_cost_score: int 0-100
     * - volatility_score: int 0-100
     */
    public function allocate(array $context = []): array
    {
        $reasonCodes = [];

        $confidence = $this->score($context, 'confidence', 50);
        $thesisStrength = $this->score($context, 'thesis_strength', 50);
        $opportunityCost = $this->score($context, 'opportunity_cost_score', 0);
        $volatility = $this->score($context, 'volatility_score', 50);
        $portfolioHeat = $context['portfolio_heat'] ?? 'low';

        $baseAllocation = (int) round(
            ($confidence * 0.40)
            + ($thesisStrength * 0.35)
            + ((100 - $opportunityCost) * 0.15)
            + ((100 - $volatility) * 0.10)
        );

        if ($portfolioHeat === 'critical') {
            $reasonCodes[] = 'critical_portfolio_heat';

            return [
                'allocation_pct' => 0,
                'reason_codes' => $reasonCodes,
                'recommendation' => 'block_new_allocation',
            ];
        }

        if ($portfolioHeat === 'high') {
            $baseAllocation = (int) round($baseAllocation * 0.50);
            $reasonCodes[] = 'portfolio_heat_reduces_allocation';
        }

        if ($opportunityCost >= 70) {
            $reasonCodes[] = 'high_opportunity_cost';
        }

        if ($volatility >= 70) {
            $baseAllocation = (int) round($baseAllocation * 0.75);
            $reasonCodes[] = 'high_volatility_reduces_allocation';
        }

        if ($confidence >= 80) {
            $reasonCodes[] = 'confidence_supports_allocation';
        }

        if ($thesisStrength >= 75) {
            $reasonCodes[] = 'thesis_strength_supports_allocation';
        }

        return [
            'allocation_pct' => max(0, min(100, $baseAllocation)),
            'reason_codes' => $reasonCodes === [] ? ['balanced_allocation_context'] : $reasonCodes,
            'recommendation' => $this->recommendation($baseAllocation),
        ];
    }

    private function recommendation(int $allocation): string
    {
        return match (true) {
            $allocation >= 75 => 'strong_allocation_candidate',
            $allocation >= 50 => 'moderate_allocation_candidate',
            $allocation >= 25 => 'reduced_allocation_candidate',
            default => 'minimal_allocation_candidate',
        };
    }

    private function score(array $context, string $key, int $default): int
    {
        return max(0, min(100, (int) ($context[$key] ?? $default)));
    }
}

<?php

namespace App\Services\AI;

use App\DTO\AI\ExecutionQualityResult;

class ExecutionQualityAiService
{
    /**
     * Observe-only execution quality service v1.
     *
     * Supported context keys:
     * - expected_entry: float
     * - actual_entry: float
     * - spread_at_entry: float
     * - max_acceptable_spread: float
     * - timing_quality: int 0-100
     * - fill_quality: int 0-100
     */
    public function evaluate(array $context = []): ExecutionQualityResult
    {
        $reasonCodes = [];

        $expectedEntry = isset($context['expected_entry']) ? (float) $context['expected_entry'] : null;
        $actualEntry = isset($context['actual_entry']) ? (float) $context['actual_entry'] : null;
        $spreadAtEntry = isset($context['spread_at_entry']) ? (float) $context['spread_at_entry'] : null;
        $maxSpread = isset($context['max_acceptable_spread']) ? (float) $context['max_acceptable_spread'] : null;
        $timingQuality = $this->score($context, 'timing_quality', 100);
        $fillQuality = $this->score($context, 'fill_quality', 100);

        $slippage = $expectedEntry !== null && $actualEntry !== null
            ? abs($actualEntry - $expectedEntry)
            : null;

        if ($slippage !== null && $expectedEntry > 0) {
            $slippageBps = ($slippage / $expectedEntry) * 10000;

            if ($slippageBps >= 20) {
                $reasonCodes[] = 'slippage_high';
            } elseif ($slippageBps >= 10) {
                $reasonCodes[] = 'slippage_elevated';
            }
        }

        if ($spreadAtEntry !== null && $maxSpread !== null && $spreadAtEntry > $maxSpread) {
            $reasonCodes[] = 'spread_above_threshold';
        }

        if ($timingQuality < 60) {
            $reasonCodes[] = 'timing_quality_weak';
        }

        if ($fillQuality < 60) {
            $reasonCodes[] = 'fill_quality_weak';
        }

        $qualityScore = (int) round(($timingQuality * 0.40) + ($fillQuality * 0.40) + ($this->spreadScore($spreadAtEntry, $maxSpread) * 0.20));

        return new ExecutionQualityResult(
            qualityScore: $qualityScore,
            expectedEntry: $expectedEntry,
            actualEntry: $actualEntry,
            slippage: $slippage,
            spreadAtEntry: $spreadAtEntry,
            reasonCodes: $reasonCodes === [] ? ['execution_quality_clear'] : $reasonCodes,
        );
    }

    private function score(array $context, string $key, int $default): int
    {
        return max(0, min(100, (int) ($context[$key] ?? $default)));
    }

    private function spreadScore(?float $spread, ?float $maxSpread): int
    {
        if ($spread === null || $maxSpread === null || $maxSpread <= 0) {
            return 100;
        }

        if ($spread <= $maxSpread) {
            return 100;
        }

        return max(0, 100 - (int) round((($spread - $maxSpread) / $maxSpread) * 100));
    }
}

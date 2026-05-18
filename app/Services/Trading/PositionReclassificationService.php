<?php

namespace App\Services\Trading;

use App\DTO\AI\PositionReclassificationResult;

class PositionReclassificationService
{
    /**
     * Observe-only position reclassification service v1.
     *
     * Supported context keys:
     * - from_strategy: string
     * - candidate_strategy: string
     * - same_direction: bool
     * - transition_count: int
     * - hard_stop_would_widen: bool
     * - averaging_down: bool
     * - expected_value_improvement: int 0-100
     * - candidate_confidence: int 0-100
     * - minimum_confidence: int 0-100
     */
    public function evaluate(array $context = []): PositionReclassificationResult
    {
        $reasonCodes = [];
        $safetyChecks = [];

        $fromStrategy = $context['from_strategy'] ?? null;
        $toStrategy = $context['candidate_strategy'] ?? null;
        $transitionCount = (int) ($context['transition_count'] ?? 0);
        $confidence = $this->score($context, 'candidate_confidence', 0);
        $minimumConfidence = $this->score($context, 'minimum_confidence', 85);
        $evImprovement = $this->score($context, 'expected_value_improvement', 0);

        if ($fromStrategy === null || $toStrategy === null) {
            $reasonCodes[] = 'strategy_context_missing';
        }

        if (($context['same_direction'] ?? true) === false) {
            $safetyChecks[] = 'failed_same_direction_check';
            $reasonCodes[] = 'direction_mismatch';
        } else {
            $safetyChecks[] = 'same_direction_ok';
        }

        if ($transitionCount >= 1) {
            $safetyChecks[] = 'failed_transition_limit_check';
            $reasonCodes[] = 'transition_limit_reached';
        } else {
            $safetyChecks[] = 'transition_limit_ok';
        }

        if (($context['hard_stop_would_widen'] ?? false) === true) {
            $safetyChecks[] = 'failed_hard_stop_check';
            $reasonCodes[] = 'hard_stop_would_widen';
        } else {
            $safetyChecks[] = 'hard_stop_ok';
        }

        if (($context['averaging_down'] ?? false) === true) {
            $safetyChecks[] = 'failed_averaging_down_check';
            $reasonCodes[] = 'averaging_down_detected';
        } else {
            $safetyChecks[] = 'no_averaging_down';
        }

        if ($evImprovement < 60) {
            $reasonCodes[] = 'expected_value_improvement_insufficient';
        } else {
            $reasonCodes[] = 'expected_value_improved';
        }

        if ($confidence < $minimumConfidence) {
            $reasonCodes[] = 'candidate_confidence_below_threshold';
        } else {
            $reasonCodes[] = 'candidate_confidence_strong';
        }

        $canReclassify = $fromStrategy !== null
            && $toStrategy !== null
            && ! in_array('direction_mismatch', $reasonCodes, true)
            && ! in_array('transition_limit_reached', $reasonCodes, true)
            && ! in_array('hard_stop_would_widen', $reasonCodes, true)
            && ! in_array('averaging_down_detected', $reasonCodes, true)
            && $evImprovement >= 60
            && $confidence >= $minimumConfidence;

        return new PositionReclassificationResult(
            canReclassify: $canReclassify,
            fromStrategy: $fromStrategy,
            toStrategy: $toStrategy,
            confidence: $confidence,
            reasonCodes: array_values(array_unique($reasonCodes)),
            safetyChecks: array_values(array_unique($safetyChecks)),
        );
    }

    private function score(array $context, string $key, int $default): int
    {
        return max(0, min(100, (int) ($context[$key] ?? $default)));
    }
}

<?php

namespace App\Services\Trading;

use App\DTO\AI\TradeLifecycleResult;
use App\Enums\AI\ThesisState;

class TradeLifecycleService
{
    public function __construct(
        private readonly ThesisStrengthService $thesisStrengthService = new ThesisStrengthService(),
    ) {
    }

    /**
     * Observe-only trade lifecycle service v1.
     *
     * Supported context keys:
     * - thesis_age_minutes: int
     * - thesis_strength inputs used by ThesisStrengthService
     * - compression_detected: bool
     * - exhaustion_detected: bool
     * - expansion_detected: bool
     */
    public function evaluate(array $context = []): TradeLifecycleResult
    {
        $thesisStrength = $this->thesisStrengthService->calculate($context);
        $warnings = [];
        $recommendations = [];

        $state = $this->state($context, $thesisStrength);

        if ($thesisStrength < 40) {
            $warnings[] = 'thesis_strength_weak';
            $recommendations[] = 'exit_watch';
        } elseif ($thesisStrength < 60) {
            $warnings[] = 'thesis_strength_softening';
            $recommendations[] = 'reduce_watch';
        } else {
            $recommendations[] = 'hold_watch';
        }

        if ((bool) ($context['compression_detected'] ?? false)) {
            $warnings[] = 'compression_detected';
            $recommendations[] = 'compression_watch';
        }

        if ((bool) ($context['exhaustion_detected'] ?? false)) {
            $warnings[] = 'exhaustion_detected';
            $recommendations[] = 'exit_watch';
        }

        return new TradeLifecycleResult(
            state: $state,
            thesisStrength: $thesisStrength,
            warnings: array_values(array_unique($warnings)),
            recommendations: array_values(array_unique($recommendations)),
        );
    }

    private function state(array $context, int $thesisStrength): ThesisState
    {
        if ((bool) ($context['exhaustion_detected'] ?? false)) {
            return ThesisState::Exhaustion;
        }

        if ((bool) ($context['compression_detected'] ?? false)) {
            return ThesisState::Compression;
        }

        if ((bool) ($context['expansion_detected'] ?? false)) {
            return ThesisState::Expansion;
        }

        if ($thesisStrength >= 75) {
            return ThesisState::Continuation;
        }

        if ($thesisStrength >= 55) {
            return ThesisState::Validation;
        }

        if ($thesisStrength > 0) {
            return ThesisState::Discovery;
        }

        return ThesisState::Unknown;
    }
}

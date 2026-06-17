<?php

namespace App\Services\AI;

use App\DTO\AI\RealityCheckResult;
use App\Enums\AI\AiRecommendedAction;

class AiRealityCheckService
{
    /**
     * Observe-only reality check v1.
     *
     * This service evaluates context and returns recommendations only.
     * It must not modify trades, candidates, orders, risk, or broker state.
     *
     * Supported context keys:
     * - setup_still_valid: bool
     * - plan_still_valid: bool
     * - regime_changed: bool
     * - news_risk_changed: bool
     * - spread_bps: numeric
     * - max_spread_bps: numeric
     * - liquidity_ok: bool
     * - volume_ok: bool
     * - confidence: int 0-100
     * - minimum_confidence: int 0-100
     */
    public function evaluate(array $context = []): RealityCheckResult
    {
        $planStillValid = $this->boolValue($context, 'plan_still_valid', true);
        $setupStillValid = $this->boolValue($context, 'setup_still_valid', true);
        $regimeChanged = $this->boolValue($context, 'regime_changed', false);
        $newsRiskChanged = $this->boolValue($context, 'news_risk_changed', false);

        $reasonCodes = [];

        if (! $planStillValid) {
            $reasonCodes[] = 'plan_invalidated';
        }

        if (! $setupStillValid) {
            $reasonCodes[] = 'setup_invalidated';
        }

        if ($regimeChanged) {
            $reasonCodes[] = 'regime_changed';
        }

        if ($newsRiskChanged) {
            $reasonCodes[] = 'news_risk_changed';
        }

        if ($this->spreadTooWide($context)) {
            $reasonCodes[] = 'spread_expanded';
        }

        if ($this->boolValue($context, 'liquidity_ok', true) === false) {
            $reasonCodes[] = 'liquidity_deteriorated';
        }

        if ($this->boolValue($context, 'volume_ok', true) === false) {
            $reasonCodes[] = 'volume_confirmation_failed';
        }

        if ($this->confidenceTooLow($context)) {
            $reasonCodes[] = 'confidence_below_threshold';
        }

        return new RealityCheckResult(
            planStillValid: $planStillValid,
            setupStillValid: $setupStillValid,
            regimeChanged: $regimeChanged,
            newsRiskChanged: $newsRiskChanged,
            recommendedAction: $this->recommendAction($reasonCodes),
            reasonCodes: $reasonCodes,
        );
    }

    private function recommendAction(array $reasonCodes): string
    {
        if (in_array('setup_invalidated', $reasonCodes, true)) {
            return AiRecommendedAction::ExitTrade->value;
        }

        if (in_array('plan_invalidated', $reasonCodes, true)) {
            return AiRecommendedAction::ForceReplan->value;
        }

        if (in_array('spread_expanded', $reasonCodes, true)
            || in_array('liquidity_deteriorated', $reasonCodes, true)
            || in_array('news_risk_changed', $reasonCodes, true)) {
            return AiRecommendedAction::ReduceRisk->value;
        }

        if (in_array('regime_changed', $reasonCodes, true)
            || in_array('volume_confirmation_failed', $reasonCodes, true)
            || in_array('confidence_below_threshold', $reasonCodes, true)) {
            return AiRecommendedAction::DowngradeCandidate->value;
        }

        return AiRecommendedAction::Continue->value;
    }

    private function boolValue(array $context, string $key, bool $default): bool
    {
        if (! array_key_exists($key, $context)) {
            return $default;
        }

        return (bool) $context[$key];
    }

    private function spreadTooWide(array $context): bool
    {
        if (! isset($context['spread_bps'], $context['max_spread_bps'])) {
            return false;
        }

        return (float) $context['spread_bps'] > (float) $context['max_spread_bps'];
    }

    private function confidenceTooLow(array $context): bool
    {
        if (! isset($context['confidence'], $context['minimum_confidence'])) {
            return false;
        }

        return (int) $context['confidence'] < (int) $context['minimum_confidence'];
    }
}

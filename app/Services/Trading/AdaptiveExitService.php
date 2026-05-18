<?php

namespace App\Services\Trading;

use App\Enums\AI\AiRecommendedAction;

class AdaptiveExitService
{
    public function __construct(
        private readonly TradeCompressionService $compressionService = new TradeCompressionService(),
        private readonly ThesisStrengthService $thesisStrengthService = new ThesisStrengthService(),
    ) {
    }

    /**
     * Observe-only adaptive exit service v1.
     *
     * Returns informational recommendations only.
     *
     * Supported context keys:
     * - thesis strength inputs
     * - compression inputs
     * - exhaustion_detected: bool
     */
    public function evaluate(array $context = []): string
    {
        $compression = $this->compressionService->analyze($context);
        $thesisStrength = $this->thesisStrengthService->calculate($context);

        if ((bool) ($context['exhaustion_detected'] ?? false)) {
            return AiRecommendedAction::ExitTrade->value;
        }

        if ($compression['compression_detected'] === true && $thesisStrength < 40) {
            return AiRecommendedAction::ExitTrade->value;
        }

        if ($compression['compression_detected'] === true || $thesisStrength < 55) {
            return AiRecommendedAction::ReduceRisk->value;
        }

        return AiRecommendedAction::HoldTrade->value;
    }
}

<?php

namespace App\Services\Trading;

class ThesisStrengthService
{
    /**
     * Observe-only thesis strength service v1.
     *
     * Supported context keys:
     * - price_structure_score: int 0-100
     * - volume_confirmation_score: int 0-100
     * - momentum_score: int 0-100
     * - regime_fit: int 0-100
     * - news_risk_score: int 0-100 where higher means more risk
     * - execution_quality: int 0-100
     */
    public function calculate(array $context = []): int
    {
        $priceStructure = $this->score($context, 'price_structure_score', 50);
        $volumeConfirmation = $this->score($context, 'volume_confirmation_score', 50);
        $momentum = $this->score($context, 'momentum_score', 50);
        $regimeFit = $this->score($context, 'regime_fit', 50);
        $newsRisk = $this->score($context, 'news_risk_score', 50);
        $executionQuality = $this->score($context, 'execution_quality', 50);

        return (int) round(
            ($priceStructure * 0.25)
            + ($volumeConfirmation * 0.20)
            + ($momentum * 0.20)
            + ($regimeFit * 0.15)
            + ((100 - $newsRisk) * 0.10)
            + ($executionQuality * 0.10)
        );
    }

    private function score(array $context, string $key, int $default): int
    {
        return max(0, min(100, (int) ($context[$key] ?? $default)));
    }
}

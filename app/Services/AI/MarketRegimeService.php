<?php

namespace App\Services\AI;

use App\DTO\AI\RegimeResult;
use App\Enums\AI\MarketRegime;

class MarketRegimeService
{
    /**
     * Observe-only market regime classifier v1.
     *
     * Supported context keys:
     * - trend_strength: int 0-100
     * - range_score: int 0-100
     * - volatility_expansion: bool
     * - volatility_compression: bool
     * - risk_on_score: int 0-100
     * - risk_off_score: int 0-100
     * - news_driven: bool
     */
    public function detect(array $context = []): RegimeResult
    {
        $reasonCodes = [];
        $secondaryRegimes = [];

        if ((bool) ($context['news_driven'] ?? false)) {
            return new RegimeResult(
                primaryRegime: MarketRegime::NewsDriven,
                confidence: 80,
                secondaryRegimes: [],
                reasonCodes: ['news_driven_market'],
            );
        }

        if ((bool) ($context['volatility_expansion'] ?? false)) {
            $reasonCodes[] = 'volatility_expansion';
            $secondaryRegimes[] = MarketRegime::Expansion->value;
        }

        if ((bool) ($context['volatility_compression'] ?? false)) {
            $reasonCodes[] = 'volatility_compression';
            $secondaryRegimes[] = MarketRegime::Compression->value;
        }

        $trendStrength = (int) ($context['trend_strength'] ?? 0);
        $rangeScore = (int) ($context['range_score'] ?? 0);
        $riskOnScore = (int) ($context['risk_on_score'] ?? 0);
        $riskOffScore = (int) ($context['risk_off_score'] ?? 0);

        if ($riskOffScore >= 70 && $riskOffScore > $riskOnScore) {
            return new RegimeResult(
                primaryRegime: MarketRegime::RiskOff,
                confidence: min(100, $riskOffScore),
                secondaryRegimes: $secondaryRegimes,
                reasonCodes: array_values(array_unique([...$reasonCodes, 'risk_off_dominant'])),
            );
        }

        if ($riskOnScore >= 70 && $riskOnScore > $riskOffScore) {
            return new RegimeResult(
                primaryRegime: MarketRegime::RiskOn,
                confidence: min(100, $riskOnScore),
                secondaryRegimes: $secondaryRegimes,
                reasonCodes: array_values(array_unique([...$reasonCodes, 'risk_on_dominant'])),
            );
        }

        if ($trendStrength >= 65 && $trendStrength >= $rangeScore) {
            return new RegimeResult(
                primaryRegime: MarketRegime::Trend,
                confidence: min(100, $trendStrength),
                secondaryRegimes: $secondaryRegimes,
                reasonCodes: array_values(array_unique([...$reasonCodes, 'trend_strength_high'])),
            );
        }

        if ($rangeScore >= 65 && $rangeScore > $trendStrength) {
            return new RegimeResult(
                primaryRegime: MarketRegime::Range,
                confidence: min(100, $rangeScore),
                secondaryRegimes: $secondaryRegimes,
                reasonCodes: array_values(array_unique([...$reasonCodes, 'range_score_high'])),
            );
        }

        if (in_array(MarketRegime::Expansion->value, $secondaryRegimes, true)) {
            return new RegimeResult(
                primaryRegime: MarketRegime::Expansion,
                confidence: 60,
                secondaryRegimes: [],
                reasonCodes: array_values(array_unique($reasonCodes)),
            );
        }

        if (in_array(MarketRegime::Compression->value, $secondaryRegimes, true)) {
            return new RegimeResult(
                primaryRegime: MarketRegime::Compression,
                confidence: 60,
                secondaryRegimes: [],
                reasonCodes: array_values(array_unique($reasonCodes)),
            );
        }

        return new RegimeResult(
            primaryRegime: MarketRegime::Unknown,
            confidence: 0,
            secondaryRegimes: [],
            reasonCodes: ['insufficient_context'],
        );
    }
}

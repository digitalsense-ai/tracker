<?php

namespace App\Services\AI;

use App\DTO\AI\RegimeResult;
use App\Enums\AI\MarketRegime;

class MarketRegimeService
{
    /**
     * Foundation market regime classifier.
     *
     * Initial implementation is intentionally conservative and
     * observe-only.
     */
    public function detect(array $context = []): RegimeResult
    {
        return new RegimeResult(
            primaryRegime: MarketRegime::Unknown,
            confidence: 0,
            secondaryRegimes: [],
            reasonCodes: ['foundation_version'],
        );
    }
}

<?php

namespace App\DTO\AI;

use App\Enums\AI\MarketRegime;

readonly class RegimeResult
{
    public function __construct(
        public MarketRegime $primaryRegime,
        public int $confidence,
        public array $secondaryRegimes = [],
        public array $reasonCodes = [],
    ) {
    }
}

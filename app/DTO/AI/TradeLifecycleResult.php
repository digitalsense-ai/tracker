<?php

namespace App\DTO\AI;

use App\Enums\AI\ThesisState;

readonly class TradeLifecycleResult
{
    public function __construct(
        public ThesisState $state,
        public int $thesisStrength,
        public array $warnings = [],
        public array $recommendations = [],
    ) {
    }
}

<?php

namespace App\DTO\AI;

readonly class RealityCheckResult
{
    public function __construct(
        public bool $planStillValid,
        public bool $setupStillValid,
        public bool $regimeChanged,
        public bool $newsRiskChanged,
        public string $recommendedAction,
        public array $reasonCodes = [],
    ) {
    }
}

<?php

namespace App\DTO\AI;

readonly class RiskGuardResult
{
    public function __construct(
        public bool $approved,
        public string $decision,
        public array $blockedBy = [],
        public array $warnings = [],
        public array $reasonCodes = [],
    ) {
    }
}

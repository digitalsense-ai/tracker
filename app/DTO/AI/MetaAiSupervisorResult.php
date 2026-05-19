<?php

namespace App\DTO\AI;

readonly class MetaAiSupervisorResult
{
    public function __construct(
        public int $systemHealth,
        public int $confidenceHealth,
        public bool $requiresAttention,
        public array $warnings = [],
        public array $recommendations = [],
    ) {
    }
}

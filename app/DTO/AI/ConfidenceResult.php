<?php

namespace App\DTO\AI;

readonly class ConfidenceResult
{
    public function __construct(
        public int $confidence,
        public int $uncertainty,
        public string $level,
        public array $reasonCodes = [],
    ) {
    }
}

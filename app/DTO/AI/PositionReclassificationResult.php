<?php

namespace App\DTO\AI;

readonly class PositionReclassificationResult
{
    public function __construct(
        public bool $canReclassify,
        public ?string $fromStrategy = null,
        public ?string $toStrategy = null,
        public int $confidence = 0,
        public array $reasonCodes = [],
        public array $safetyChecks = [],
    ) {
    }
}

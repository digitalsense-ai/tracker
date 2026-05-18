<?php

namespace App\DTO\AI;

readonly class ExecutionQualityResult
{
    public function __construct(
        public int $qualityScore,
        public ?float $expectedEntry = null,
        public ?float $actualEntry = null,
        public ?float $slippage = null,
        public ?float $spreadAtEntry = null,
        public array $reasonCodes = [],
    ) {
    }
}

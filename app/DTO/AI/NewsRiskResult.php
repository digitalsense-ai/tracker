<?php

namespace App\DTO\AI;

readonly class NewsRiskResult
{
    public function __construct(
        public int $riskScore,
        public string $riskLevel,
        public bool $hasHighImpactEvent = false,
        public array $headlines = [],
        public array $reasonCodes = [],
    ) {
    }
}

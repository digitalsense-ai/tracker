<?php

namespace App\Services\Trading;

class TradeCompressionService
{
    /**
     * Observe-only trade compression service v1.
     *
     * Supported context keys:
     * - momentum_score: int 0-100
     * - volatility_score: int 0-100
     * - range_contraction_score: int 0-100
     * - volume_fade_score: int 0-100
     */
    public function analyze(array $context = []): array
    {
        $reasonCodes = [];

        $momentum = $this->score($context, 'momentum_score', 50);
        $volatility = $this->score($context, 'volatility_score', 50);
        $rangeContraction = $this->score($context, 'range_contraction_score', 0);
        $volumeFade = $this->score($context, 'volume_fade_score', 0);

        if ($momentum < 40) {
            $reasonCodes[] = 'momentum_compressed';
        }

        if ($volatility < 40) {
            $reasonCodes[] = 'volatility_compressed';
        }

        if ($rangeContraction >= 70) {
            $reasonCodes[] = 'range_contraction_high';
        }

        if ($volumeFade >= 70) {
            $reasonCodes[] = 'volume_fade_high';
        }

        $compressionDetected = count($reasonCodes) >= 2;

        return [
            'compression_detected' => $compressionDetected,
            'compression_score' => $compressionDetected ? min(100, count($reasonCodes) * 25) : 0,
            'reason_codes' => $reasonCodes === [] ? ['compression_not_detected'] : $reasonCodes,
        ];
    }

    private function score(array $context, string $key, int $default): int
    {
        return max(0, min(100, (int) ($context[$key] ?? $default)));
    }
}

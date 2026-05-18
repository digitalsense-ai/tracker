<?php

namespace App\Services\Trading;

class DynamicPositionScalingService
{
    /**
     * Observe-only dynamic position scaling service v1.
     *
     * Returns informational recommendations only.
     *
     * Supported context keys:
     * - confidence: int 0-100
     * - thesis_strength: int 0-100
     * - portfolio_heat: low|medium|high|critical
     * - execution_quality: int 0-100
     * - current_size_pct: int 0-100
     * - max_size_pct: int 0-100
     */
    public function evaluate(array $context = []): array
    {
        $reasonCodes = [];

        $confidence = $this->score($context, 'confidence', 50);
        $thesisStrength = $this->score($context, 'thesis_strength', 50);
        $executionQuality = $this->score($context, 'execution_quality', 50);
        $currentSize = $this->score($context, 'current_size_pct', 0);
        $maxSize = $this->score($context, 'max_size_pct', 100);
        $portfolioHeat = $context['portfolio_heat'] ?? 'low';

        if (in_array($portfolioHeat, ['high', 'critical'], true)) {
            $reasonCodes[] = 'portfolio_heat_blocks_scaling_up';

            return [
                'action' => $currentSize > 0 ? 'scale_down_watch' : 'hold',
                'target_size_pct' => $currentSize > 0 ? max(0, $currentSize - 25) : 0,
                'reason_codes' => $reasonCodes,
            ];
        }

        if ($confidence >= 80 && $thesisStrength >= 75 && $executionQuality >= 70 && $currentSize < $maxSize) {
            $reasonCodes[] = 'confidence_strong';
            $reasonCodes[] = 'thesis_strength_strong';
            $reasonCodes[] = 'execution_quality_acceptable';

            return [
                'action' => 'scale_up_watch',
                'target_size_pct' => min($maxSize, $currentSize + 25),
                'reason_codes' => $reasonCodes,
            ];
        }

        if ($confidence < 50 || $thesisStrength < 50 || $executionQuality < 50) {
            if ($confidence < 50) {
                $reasonCodes[] = 'confidence_weak';
            }

            if ($thesisStrength < 50) {
                $reasonCodes[] = 'thesis_strength_weak';
            }

            if ($executionQuality < 50) {
                $reasonCodes[] = 'execution_quality_weak';
            }

            return [
                'action' => $currentSize > 0 ? 'scale_down_watch' : 'hold',
                'target_size_pct' => $currentSize > 0 ? max(0, $currentSize - 25) : 0,
                'reason_codes' => $reasonCodes,
            ];
        }

        return [
            'action' => 'hold',
            'target_size_pct' => $currentSize,
            'reason_codes' => ['balanced_scaling_context'],
        ];
    }

    private function score(array $context, string $key, int $default): int
    {
        return max(0, min(100, (int) ($context[$key] ?? $default)));
    }
}

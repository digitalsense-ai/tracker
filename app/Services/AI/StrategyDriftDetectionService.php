<?php

namespace App\Services\AI;

class StrategyDriftDetectionService
{
    /**
     * Observe-only strategy drift detection v1.
     *
     * Supported context keys:
     * - baseline_win_rate: int 0-100
     * - recent_win_rate: int 0-100
     * - baseline_avg_r: int 0-100
     * - recent_avg_r: int 0-100
     * - false_positive_rate: int 0-100
     * - sample_size: int
     */
    public function analyze(array $context = []): array
    {
        $baselineWinRate = $this->score($context, 'baseline_win_rate', 50);
        $recentWinRate = $this->score($context, 'recent_win_rate', 50);
        $baselineAvgR = $this->score($context, 'baseline_avg_r', 50);
        $recentAvgR = $this->score($context, 'recent_avg_r', 50);
        $falsePositiveRate = $this->score($context, 'false_positive_rate', 0);
        $sampleSize = max(0, (int) ($context['sample_size'] ?? 0));

        $winRateGap = $baselineWinRate - $recentWinRate;
        $avgRGap = $baselineAvgR - $recentAvgR;
        $reasonCodes = [];

        if ($sampleSize < 20) {
            $reasonCodes[] = 'sample_size_low';
        }

        if ($winRateGap >= 20) {
            $reasonCodes[] = 'win_rate_drift_high';
        } elseif ($winRateGap >= 10) {
            $reasonCodes[] = 'win_rate_drift_medium';
        }

        if ($avgRGap >= 20) {
            $reasonCodes[] = 'avg_r_drift_high';
        } elseif ($avgRGap >= 10) {
            $reasonCodes[] = 'avg_r_drift_medium';
        }

        if ($falsePositiveRate >= 40) {
            $reasonCodes[] = 'false_positive_rate_high';
        }

        if ($reasonCodes === []) {
            $reasonCodes[] = 'strategy_drift_not_detected';
        }

        return [
            'drift_detected' => $this->driftDetected($reasonCodes),
            'status' => $this->status($reasonCodes, $sampleSize),
            'win_rate_gap' => $winRateGap,
            'avg_r_gap' => $avgRGap,
            'false_positive_rate' => $falsePositiveRate,
            'sample_size' => $sampleSize,
            'recommendation' => $this->recommendation($reasonCodes, $sampleSize),
            'reason_codes' => $reasonCodes,
        ];
    }

    private function driftDetected(array $reasonCodes): bool
    {
        return count(array_filter($reasonCodes, fn (string $code): bool => $code !== 'sample_size_low' && $code !== 'strategy_drift_not_detected')) > 0;
    }

    private function status(array $reasonCodes, int $sampleSize): string
    {
        if ($sampleSize < 20) {
            return 'insufficient_sample';
        }

        if (in_array('win_rate_drift_high', $reasonCodes, true) || in_array('avg_r_drift_high', $reasonCodes, true)) {
            return 'strategy_drift_high';
        }

        if ($this->driftDetected($reasonCodes)) {
            return 'strategy_drift_watch';
        }

        return 'stable';
    }

    private function recommendation(array $reasonCodes, int $sampleSize): string
    {
        if ($sampleSize < 20) {
            return 'collect_more_samples';
        }

        if (in_array('win_rate_drift_high', $reasonCodes, true) || in_array('avg_r_drift_high', $reasonCodes, true)) {
            return 'review_strategy_degradation';
        }

        if ($this->driftDetected($reasonCodes)) {
            return 'monitor_strategy_drift';
        }

        return 'no_strategy_drift_action';
    }

    private function score(array $context, string $key, int $default): int
    {
        return max(0, min(100, (int) ($context[$key] ?? $default)));
    }
}

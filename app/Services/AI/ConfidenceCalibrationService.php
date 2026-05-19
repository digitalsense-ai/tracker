<?php

namespace App\Services\AI;

class ConfidenceCalibrationService
{
    /**
     * Observe-only confidence calibration service v1.
     *
     * Compares expected confidence against realized outcome quality.
     *
     * Supported context keys:
     * - average_confidence: int 0-100
     * - realized_quality: int 0-100
     * - sample_size: int
     */
    public function analyze(array $context = []): array
    {
        $averageConfidence = $this->score($context, 'average_confidence', 50);
        $realizedQuality = $this->score($context, 'realized_quality', 50);
        $sampleSize = max(0, (int) ($context['sample_size'] ?? 0));

        $calibrationGap = $averageConfidence - $realizedQuality;
        $reasonCodes = [];

        if ($sampleSize < 20) {
            $reasonCodes[] = 'sample_size_low';
        }

        if ($calibrationGap >= 25) {
            $reasonCodes[] = 'confidence_overestimated';
        } elseif ($calibrationGap <= -25) {
            $reasonCodes[] = 'confidence_underestimated';
        } elseif (abs($calibrationGap) >= 10) {
            $reasonCodes[] = 'confidence_misaligned';
        } else {
            $reasonCodes[] = 'confidence_calibrated';
        }

        return [
            'average_confidence' => $averageConfidence,
            'realized_quality' => $realizedQuality,
            'calibration_gap' => $calibrationGap,
            'sample_size' => $sampleSize,
            'status' => $this->status($calibrationGap, $sampleSize),
            'recommendation' => $this->recommendation($calibrationGap, $sampleSize),
            'reason_codes' => $reasonCodes,
        ];
    }

    private function status(int $gap, int $sampleSize): string
    {
        if ($sampleSize < 20) {
            return 'insufficient_sample';
        }

        return match (true) {
            abs($gap) >= 25 => 'calibration_drift_high',
            abs($gap) >= 10 => 'calibration_drift_medium',
            default => 'calibrated',
        };
    }

    private function recommendation(int $gap, int $sampleSize): string
    {
        if ($sampleSize < 20) {
            return 'collect_more_samples';
        }

        return match (true) {
            $gap >= 25 => 'review_confidence_overestimation',
            $gap <= -25 => 'review_confidence_underestimation',
            abs($gap) >= 10 => 'monitor_confidence_alignment',
            default => 'no_calibration_action',
        };
    }

    private function score(array $context, string $key, int $default): int
    {
        return max(0, min(100, (int) ($context[$key] ?? $default)));
    }
}

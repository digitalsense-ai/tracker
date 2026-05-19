<?php

namespace App\Services\AI;

class AdaptiveDriftSnapshotService
{
    /**
     * Observe-only adaptive drift snapshot service v1.
     *
     * Converts adaptive state diffs into normalized drift summaries.
     */
    public function snapshot(array $diff): array
    {
        $severityCounts = [
            'minor' => 0,
            'moderate' => 0,
            'critical' => 0,
        ];

        foreach (($diff['changes'] ?? []) as $change) {
            $severity = $change['severity'] ?? 'minor';

            if (isset($severityCounts[$severity])) {
                $severityCounts[$severity]++;
            }
        }

        return [
            'drift_detected' => ($diff['has_changes'] ?? false) === true,
            'change_count' => $diff['change_count'] ?? 0,
            'minor_drift_count' => $severityCounts['minor'],
            'moderate_drift_count' => $severityCounts['moderate'],
            'critical_drift_count' => $severityCounts['critical'],
            'overall_severity' => $this->overallSeverity($severityCounts),
        ];
    }

    private function overallSeverity(array $severityCounts): string
    {
        if ($severityCounts['critical'] > 0) {
            return 'critical';
        }

        if ($severityCounts['moderate'] > 1) {
            return 'moderate';
        }

        if ($severityCounts['minor'] > 0) {
            return 'minor';
        }

        return 'stable';
    }
}

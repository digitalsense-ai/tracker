<?php

namespace App\Services\Analytics;

class AnalyticsExportService
{
    /**
     * Observe-only analytics export service v1.
     *
     * Normalizes analytics datasets for external BI/reporting tools.
     */
    public function dataset(string $type, array $rows = []): array
    {
        return [
            'dataset_type' => $type,
            'generated_at' => now()->toIso8601String(),
            'row_count' => count($rows),
            'format' => config('analytics_exports.default_format', 'json'),
            'read_only' => true,
            'rows' => array_map(fn (array $row): array => $this->sanitize($row), $rows),
        ];
    }

    private function sanitize(array $row): array
    {
        if (config('analytics_exports.sanitize_account_identifiers', true)) {
            unset($row['account_id']);
            unset($row['broker_account']);
        }

        return $row;
    }
}

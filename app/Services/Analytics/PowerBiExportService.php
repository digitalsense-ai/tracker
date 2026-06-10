<?php

namespace App\Services\Analytics;

class PowerBiExportService
{
    /**
     * Observe-only Power BI export formatter v1.
     *
     * Produces flattened BI-friendly datasets.
     */
    public function flatten(array $dataset): array
    {
        return array_map(function (array $row) use ($dataset): array {
            return [
                'dataset_type' => $dataset['dataset_type'] ?? 'unknown',
                'generated_at' => $dataset['generated_at'] ?? null,
                ...$row,
            ];
        }, $dataset['rows'] ?? []);
    }
}

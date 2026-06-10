<?php

namespace Tests\Unit;

use App\Services\Analytics\AnalyticsExportService;
use App\Services\Analytics\PowerBiExportService;
use PHPUnit\Framework\TestCase;

class AnalyticsInfrastructureTest extends TestCase
{
    public function test_export_service_builds_dataset(): void
    {
        $service = new AnalyticsExportService();

        $dataset = $service->dataset('trades', [
            ['symbol' => 'NVDA', 'pnl' => 1200],
        ]);

        $this->assertSame('trades', $dataset['dataset_type']);
        $this->assertSame(1, $dataset['row_count']);
        $this->assertTrue($dataset['read_only']);
    }

    public function test_power_bi_formatter_flattens_dataset(): void
    {
        $service = new PowerBiExportService();

        $rows = $service->flatten([
            'dataset_type' => 'trades',
            'generated_at' => '2026-05-19T00:00:00Z',
            'rows' => [
                ['symbol' => 'NVDA', 'pnl' => 1200],
            ],
        ]);

        $this->assertSame('trades', $rows[0]['dataset_type']);
        $this->assertSame('NVDA', $rows[0]['symbol']);
    }
}

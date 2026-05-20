<?php

namespace Tests\Unit;

use App\Services\Observability\AdaptiveHealthSnapshotService;
use App\Services\Observability\DecisionTraceService;
use PHPUnit\Framework\TestCase;

class AdaptiveObservabilityTest extends TestCase
{
    public function test_health_snapshot_detects_degraded_state(): void
    {
        config()->set('adaptive_observability.thresholds.degraded_health', 70);

        $service = new AdaptiveHealthSnapshotService();

        $snapshot = $service->snapshot([
            'health' => 55,
            'module' => 'risk-guard',
            'cycle_ms' => 450,
        ]);

        $this->assertSame('degraded', $snapshot['status']);
        $this->assertSame('risk-guard', $snapshot['module']);
        $this->assertTrue($snapshot['observe_only']);
    }

    public function test_decision_trace_builds_normalized_trace(): void
    {
        $service = new DecisionTraceService();

        $trace = $service->trace([
            'decision_type' => 'trade_review',
            'confidence' => 82,
            'reason_codes' => ['trend_confirmed'],
        ]);

        $this->assertSame('trade_review', $trace['decision_type']);
        $this->assertSame(82, $trace['confidence']);
        $this->assertContains('trend_confirmed', $trace['reason_codes']);
        $this->assertTrue($trace['observe_only']);
    }
}

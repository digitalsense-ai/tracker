<?php

namespace Tests\Unit;

use App\Services\Explainability\AdaptiveTimelineExplorerService;
use App\Services\Explainability\DecisionExplorerService;
use App\Services\Explainability\DriftExplorerService;
use App\Services\Explainability\ExplainabilityFormatterService;
use App\Services\Explainability\GovernanceExplorerService;
use PHPUnit\Framework\TestCase;

class ExplainabilityInfrastructureTest extends TestCase
{
    public function test_decision_explorer_normalizes_decision(): void
    {
        $service = new DecisionExplorerService();

        $result = $service->inspect([
            'decision_type' => 'trade_review',
            'confidence' => 88,
            'reason_codes' => ['trend_confirmed'],
        ]);

        $this->assertSame('trade_review', $result['decision_type']);
        $this->assertSame(88, $result['confidence']);
        $this->assertContains('trend_confirmed', $result['reason_codes']);
        $this->assertTrue($result['observe_only']);
    }

    public function test_drift_explorer_normalizes_drift(): void
    {
        $service = new DriftExplorerService();

        $result = $service->inspect([
            'drift_type' => 'confidence',
            'severity' => 'moderate',
        ]);

        $this->assertSame('confidence', $result['drift_type']);
        $this->assertSame('moderate', $result['severity']);
    }

    public function test_governance_explorer_normalizes_governance_state(): void
    {
        $service = new GovernanceExplorerService();

        $result = $service->inspect([
            'policy' => 'risk_policy',
            'safe_mode' => true,
        ]);

        $this->assertSame('risk_policy', $result['policy']);
        $this->assertTrue($result['safe_mode']);
    }

    public function test_timeline_explorer_normalizes_events(): void
    {
        $service = new AdaptiveTimelineExplorerService();

        $result = $service->inspect([
            'events' => [
                ['type' => 'confidence_drop'],
            ],
        ]);

        $this->assertSame(1, $result['event_count']);
        $this->assertTrue($result['observe_only']);
    }

    public function test_formatter_builds_dashboard_friendly_output(): void
    {
        $service = new ExplainabilityFormatterService();

        $result = $service->format([
            'summary' => 'Confidence decreased after volatility spike.',
            'decision_type' => 'trade_review',
        ]);

        $this->assertSame('trade_review', $result['decision_type']);
        $this->assertTrue($result['read_only']);
    }
}

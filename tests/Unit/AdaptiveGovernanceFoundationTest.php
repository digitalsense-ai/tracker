<?php

namespace Tests\Unit;

use App\Services\Governance\AdaptiveEscalationService;
use App\Services\Governance\AdaptivePolicyEngineService;
use App\Services\Governance\GovernanceTimelineService;
use App\Services\Governance\RecommendationPriorityService;
use App\Services\Governance\SafeModeGovernanceService;
use PHPUnit\Framework\TestCase;

class AdaptiveGovernanceFoundationTest extends TestCase
{
    public function test_policy_engine_recommends_safe_mode(): void
    {
        $service = new AdaptivePolicyEngineService();

        $result = $service->evaluate([
            'portfolio_heat' => 'critical',
            'confidence_drift' => 'high',
            'execution_drift' => 'high',
        ]);

        $this->assertSame('governance_attention_required', $result['policy_status']);
        $this->assertContains('safe_mode_recommended', $result['recommendations']);
        $this->assertTrue($result['observe_only']);
    }

    public function test_safe_mode_service_normalizes_recommendations(): void
    {
        $service = new SafeModeGovernanceService();

        $result = $service->recommend([
            'safe_mode_recommended' => true,
            'manual_review_recommended' => true,
        ]);

        $this->assertTrue($result['safe_mode_recommended']);
        $this->assertTrue($result['manual_review_recommended']);
    }

    public function test_escalation_service_normalizes_escalation_state(): void
    {
        $service = new AdaptiveEscalationService();

        $result = $service->escalate([
            'operator_escalation' => true,
            'severity' => 'high',
        ]);

        $this->assertTrue($result['operator_escalation']);
        $this->assertSame('high', $result['severity']);
    }

    public function test_governance_timeline_creates_event(): void
    {
        $service = new GovernanceTimelineService();

        $result = $service->event([
            'event_type' => 'safe_mode_review',
        ]);

        $this->assertSame('safe_mode_review', $result['event_type']);
        $this->assertTrue($result['observe_only']);
    }

    public function test_priority_service_ranks_recommendations(): void
    {
        $service = new RecommendationPriorityService();

        $result = $service->rank([
            'severity' => 90,
            'impact' => 80,
            'confidence' => 70,
            'urgency' => 75,
        ]);

        $this->assertSame('critical', $result['priority_level']);
        $this->assertTrue($result['observe_only']);
    }
}

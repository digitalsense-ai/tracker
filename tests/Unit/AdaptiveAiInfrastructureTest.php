<?php

namespace Tests\Unit;

use App\Enums\AI\AiRecommendedAction;
use App\Enums\AI\MarketRegime;
use App\Enums\AI\PortfolioHeatLevel;
use App\Enums\AI\ThesisState;
use App\Services\AI\AiRealityCheckService;
use App\Services\AI\ConfidenceEngineService;
use App\Services\AI\MarketRegimeService;
use App\Services\Risk\PortfolioRiskService;
use App\Services\Trading\TradeLifecycleService;
use PHPUnit\Framework\TestCase;

class AdaptiveAiInfrastructureTest extends TestCase
{
    public function test_reality_check_defaults_to_observe_only_continue_state(): void
    {
        $result = (new AiRealityCheckService())->evaluate();

        $this->assertTrue($result->planStillValid);
        $this->assertTrue($result->setupStillValid);
        $this->assertFalse($result->regimeChanged);
        $this->assertFalse($result->newsRiskChanged);
        $this->assertSame(AiRecommendedAction::Continue->value, $result->recommendedAction);
    }

    public function test_reality_check_recommends_exit_when_setup_is_invalidated(): void
    {
        $result = (new AiRealityCheckService())->evaluate([
            'setup_still_valid' => false,
        ]);

        $this->assertFalse($result->setupStillValid);
        $this->assertSame(AiRecommendedAction::ExitTrade->value, $result->recommendedAction);
        $this->assertContains('setup_invalidated', $result->reasonCodes);
    }

    public function test_reality_check_recommends_replan_when_plan_is_invalidated(): void
    {
        $result = (new AiRealityCheckService())->evaluate([
            'plan_still_valid' => false,
        ]);

        $this->assertFalse($result->planStillValid);
        $this->assertSame(AiRecommendedAction::ForceReplan->value, $result->recommendedAction);
        $this->assertContains('plan_invalidated', $result->reasonCodes);
    }

    public function test_reality_check_recommends_reduce_risk_on_spread_expansion(): void
    {
        $result = (new AiRealityCheckService())->evaluate([
            'spread_bps' => 35,
            'max_spread_bps' => 20,
        ]);

        $this->assertSame(AiRecommendedAction::ReduceRisk->value, $result->recommendedAction);
        $this->assertContains('spread_expanded', $result->reasonCodes);
    }

    public function test_reality_check_recommends_downgrade_on_low_confidence(): void
    {
        $result = (new AiRealityCheckService())->evaluate([
            'confidence' => 62,
            'minimum_confidence' => 75,
        ]);

        $this->assertSame(AiRecommendedAction::DowngradeCandidate->value, $result->recommendedAction);
        $this->assertContains('confidence_below_threshold', $result->reasonCodes);
    }

    public function test_confidence_engine_returns_foundation_result(): void
    {
        $result = (new ConfidenceEngineService())->calculate();

        $this->assertSame(50, $result->confidence);
        $this->assertSame(50, $result->uncertainty);
        $this->assertSame('neutral', $result->level);
        $this->assertContains('foundation_version', $result->reasonCodes);
    }

    public function test_market_regime_defaults_to_unknown(): void
    {
        $result = (new MarketRegimeService())->detect();

        $this->assertSame(MarketRegime::Unknown, $result->primaryRegime);
        $this->assertSame(0, $result->confidence);
        $this->assertContains('foundation_version', $result->reasonCodes);
    }

    public function test_portfolio_risk_defaults_to_low_heat(): void
    {
        $result = (new PortfolioRiskService())->analyze();

        $this->assertSame(0, $result->riskScore);
        $this->assertSame(PortfolioHeatLevel::Low, $result->heatLevel);
        $this->assertSame([], $result->riskFactors);
    }

    public function test_trade_lifecycle_defaults_to_unknown_state(): void
    {
        $result = (new TradeLifecycleService())->evaluate();

        $this->assertSame(ThesisState::Unknown, $result->state);
        $this->assertSame(0, $result->thesisStrength);
    }
}

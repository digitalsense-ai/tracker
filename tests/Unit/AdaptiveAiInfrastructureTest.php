<?php

namespace Tests\Unit;

use App\Enums\AI\AiRecommendedAction;
use App\Enums\AI\MarketRegime;
use App\Enums\AI\PortfolioHeatLevel;
use App\Enums\AI\ThesisState;
use App\Services\AI\AiRealityCheckService;
use App\Services\AI\ConfidenceEngineService;
use App\Services\AI\MarketRegimeService;
use App\Services\Risk\CapitalAllocationService;
use App\Services\Risk\OpportunityCostService;
use App\Services\Risk\PortfolioRiskService;
use App\Services\Trading\DynamicPositionScalingService;
use App\Services\Trading\PositionReclassificationService;
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

    public function test_confidence_engine_returns_foundation_result(): void
    {
        $result = (new ConfidenceEngineService())->calculate();

        $this->assertSame(50, $result->confidence);
        $this->assertSame(50, $result->uncertainty);
        $this->assertSame('neutral', $result->level);
    }

    public function test_market_regime_defaults_to_unknown(): void
    {
        $result = (new MarketRegimeService())->detect();

        $this->assertSame(MarketRegime::Unknown, $result->primaryRegime);
        $this->assertSame(0, $result->confidence);
    }

    public function test_portfolio_risk_defaults_to_low_heat(): void
    {
        $result = (new PortfolioRiskService())->analyze();

        $this->assertSame(0, $result->riskScore);
        $this->assertSame(PortfolioHeatLevel::Low, $result->heatLevel);
        $this->assertSame([], $result->riskFactors);
    }

    public function test_position_reclassification_allows_safe_high_confidence_transition(): void
    {
        $result = (new PositionReclassificationService())->evaluate([
            'from_strategy' => 'ORB Retest',
            'candidate_strategy' => 'Momentum Continuation',
            'same_direction' => true,
            'transition_count' => 0,
            'hard_stop_would_widen' => false,
            'averaging_down' => false,
            'expected_value_improvement' => 80,
            'candidate_confidence' => 90,
            'minimum_confidence' => 85,
        ]);

        $this->assertTrue($result->canReclassify);
        $this->assertSame('ORB Retest', $result->fromStrategy);
        $this->assertSame('Momentum Continuation', $result->toStrategy);
        $this->assertContains('expected_value_improved', $result->reasonCodes);
        $this->assertContains('candidate_confidence_strong', $result->reasonCodes);
    }

    public function test_position_reclassification_blocks_unsafe_transition(): void
    {
        $result = (new PositionReclassificationService())->evaluate([
            'from_strategy' => 'ORB Retest',
            'candidate_strategy' => 'Mean Reversion',
            'same_direction' => false,
            'transition_count' => 1,
            'hard_stop_would_widen' => true,
            'averaging_down' => true,
            'expected_value_improvement' => 20,
            'candidate_confidence' => 50,
            'minimum_confidence' => 85,
        ]);

        $this->assertFalse($result->canReclassify);
        $this->assertContains('direction_mismatch', $result->reasonCodes);
        $this->assertContains('transition_limit_reached', $result->reasonCodes);
        $this->assertContains('hard_stop_would_widen', $result->reasonCodes);
        $this->assertContains('averaging_down_detected', $result->reasonCodes);
    }

    public function test_dynamic_position_scaling_recommends_scale_up_watch(): void
    {
        $result = (new DynamicPositionScalingService())->evaluate([
            'confidence' => 90,
            'thesis_strength' => 85,
            'portfolio_heat' => 'low',
            'execution_quality' => 80,
            'current_size_pct' => 25,
            'max_size_pct' => 100,
        ]);

        $this->assertSame('scale_up_watch', $result['action']);
        $this->assertSame(50, $result['target_size_pct']);
        $this->assertContains('confidence_strong', $result['reason_codes']);
    }

    public function test_dynamic_position_scaling_blocks_scale_up_on_high_heat(): void
    {
        $result = (new DynamicPositionScalingService())->evaluate([
            'confidence' => 90,
            'thesis_strength' => 85,
            'portfolio_heat' => 'high',
            'execution_quality' => 80,
            'current_size_pct' => 50,
            'max_size_pct' => 100,
        ]);

        $this->assertSame('scale_down_watch', $result['action']);
        $this->assertContains('portfolio_heat_blocks_scaling_up', $result['reason_codes']);
    }

    public function test_opportunity_cost_detects_better_opportunity(): void
    {
        $result = (new OpportunityCostService())->evaluate([
            'current_trade_ev' => 40,
            'best_alternative_ev' => 85,
            'capital_locked_pct' => 80,
            'portfolio_heat' => 'high',
        ]);

        $this->assertGreaterThanOrEqual(50, $result['score']);
        $this->assertSame(45, $result['ev_gap']);
        $this->assertContains('better_opportunity_detected', $result['reason_codes']);
        $this->assertContains('capital_locked_high', $result['reason_codes']);
    }

    public function test_capital_allocation_blocks_on_critical_heat(): void
    {
        $result = (new CapitalAllocationService())->allocate([
            'confidence' => 95,
            'thesis_strength' => 95,
            'portfolio_heat' => 'critical',
            'opportunity_cost_score' => 0,
            'volatility_score' => 20,
        ]);

        $this->assertSame(0, $result['allocation_pct']);
        $this->assertSame('block_new_allocation', $result['recommendation']);
        $this->assertContains('critical_portfolio_heat', $result['reason_codes']);
    }

    public function test_capital_allocation_supports_strong_candidate(): void
    {
        $result = (new CapitalAllocationService())->allocate([
            'confidence' => 90,
            'thesis_strength' => 90,
            'portfolio_heat' => 'low',
            'opportunity_cost_score' => 10,
            'volatility_score' => 20,
        ]);

        $this->assertGreaterThanOrEqual(75, $result['allocation_pct']);
        $this->assertSame('strong_allocation_candidate', $result['recommendation']);
        $this->assertContains('confidence_supports_allocation', $result['reason_codes']);
    }

    public function test_trade_lifecycle_defaults_to_unknown_state(): void
    {
        $result = (new TradeLifecycleService())->evaluate();

        $this->assertSame(ThesisState::Validation, $result->state);
        $this->assertSame(50, $result->thesisStrength);
    }
}

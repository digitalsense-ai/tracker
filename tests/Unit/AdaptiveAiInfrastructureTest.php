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
        $this->assertContains('balanced_context', $result->reasonCodes);
    }

    public function test_confidence_engine_detects_high_confidence_context(): void
    {
        $result = (new ConfidenceEngineService())->calculate([
            'setup_score' => 90,
            'regime_fit' => 85,
            'liquidity_score' => 80,
            'news_risk_score' => 10,
            'execution_quality' => 80,
            'recent_model_score' => 85,
        ]);

        $this->assertSame('high', $result->level);
        $this->assertGreaterThanOrEqual(80, $result->confidence);
        $this->assertContains('setup_quality_strong', $result->reasonCodes);
        $this->assertContains('regime_fit_strong', $result->reasonCodes);
    }

    public function test_confidence_engine_detects_weak_context(): void
    {
        $result = (new ConfidenceEngineService())->calculate([
            'setup_score' => 35,
            'regime_fit' => 30,
            'liquidity_score' => 40,
            'news_risk_score' => 85,
            'execution_quality' => 35,
            'recent_model_score' => 30,
        ]);

        $this->assertSame('low', $result->level);
        $this->assertContains('setup_quality_weak', $result->reasonCodes);
        $this->assertContains('regime_fit_weak', $result->reasonCodes);
        $this->assertContains('news_risk_high', $result->reasonCodes);
    }

    public function test_market_regime_defaults_to_unknown(): void
    {
        $result = (new MarketRegimeService())->detect();

        $this->assertSame(MarketRegime::Unknown, $result->primaryRegime);
        $this->assertSame(0, $result->confidence);
        $this->assertContains('insufficient_context', $result->reasonCodes);
    }

    public function test_market_regime_detects_news_driven_market(): void
    {
        $result = (new MarketRegimeService())->detect([
            'news_driven' => true,
        ]);

        $this->assertSame(MarketRegime::NewsDriven, $result->primaryRegime);
        $this->assertSame(80, $result->confidence);
        $this->assertContains('news_driven_market', $result->reasonCodes);
    }

    public function test_market_regime_detects_trend_with_expansion_secondary(): void
    {
        $result = (new MarketRegimeService())->detect([
            'trend_strength' => 78,
            'range_score' => 30,
            'volatility_expansion' => true,
        ]);

        $this->assertSame(MarketRegime::Trend, $result->primaryRegime);
        $this->assertSame(78, $result->confidence);
        $this->assertContains(MarketRegime::Expansion->value, $result->secondaryRegimes);
        $this->assertContains('trend_strength_high', $result->reasonCodes);
        $this->assertContains('volatility_expansion', $result->reasonCodes);
    }

    public function test_market_regime_detects_risk_off_context(): void
    {
        $result = (new MarketRegimeService())->detect([
            'risk_off_score' => 82,
            'risk_on_score' => 20,
        ]);

        $this->assertSame(MarketRegime::RiskOff, $result->primaryRegime);
        $this->assertSame(82, $result->confidence);
        $this->assertContains('risk_off_dominant', $result->reasonCodes);
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

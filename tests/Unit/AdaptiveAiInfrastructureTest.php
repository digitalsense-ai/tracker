<?php

namespace Tests\Unit;

use App\Enums\AI\AiRecommendedAction;
use App\Enums\AI\MarketRegime;
use App\Enums\AI\PortfolioHeatLevel;
use App\Enums\AI\ThesisState;
use App\Services\AI\AiReasoningDriftService;
use App\Services\AI\AiRealityCheckService;
use App\Services\AI\ConfidenceCalibrationService;
use App\Services\AI\ConfidenceEngineService;
use App\Services\AI\ExecutionDriftDetectionService;
use App\Services\AI\MarketRegimeService;
use App\Services\Risk\CapitalAllocationService;
use App\Services\Risk\OpportunityCostService;
use App\Services\Risk\PortfolioRiskService;
use App\Services\Risk\RiskGuardService;
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
        $this->assertContains('portfolio_heat_low', $result->recommendations);
    }

    public function test_portfolio_risk_detects_high_correlation_and_sector_exposure(): void
    {
        $result = (new PortfolioRiskService())->analyze([
            'open_risk_score' => 40,
            'correlation_score' => 85,
            'sector_exposure_score' => 90,
            'drawdown_score' => 30,
            'volatility_score' => 30,
            'execution_risk_score' => 20,
            'news_risk_score' => 20,
        ]);

        $this->assertSame(PortfolioHeatLevel::Medium, $result->heatLevel);
        $this->assertContains('correlation_risk_high', $result->riskFactors);
        $this->assertContains('sector_exposure_high', $result->riskFactors);
        $this->assertContains('portfolio_heat_medium_watch', $result->recommendations);
    }

    public function test_portfolio_risk_detects_critical_heat(): void
    {
        $result = (new PortfolioRiskService())->analyze([
            'open_risk_score' => 100,
            'correlation_score' => 100,
            'sector_exposure_score' => 100,
            'drawdown_score' => 100,
            'volatility_score' => 100,
            'execution_risk_score' => 100,
            'news_risk_score' => 100,
        ]);

        $this->assertSame(PortfolioHeatLevel::Critical, $result->heatLevel);
        $this->assertSame(100, $result->riskScore);
        $this->assertContains('portfolio_heat_critical_watch', $result->recommendations);
        $this->assertContains('reduce_new_risk_watch', $result->recommendations);
    }

    public function test_risk_guard_allows_clear_context(): void
    {
        $result = (new RiskGuardService())->evaluate();

        $this->assertTrue($result->approved);
        $this->assertSame('allow', $result->decision);
        $this->assertSame([], $result->blockedBy);
        $this->assertContains('risk_guard_clear', $result->reasonCodes);
    }

    public function test_risk_guard_blocks_on_kill_switch(): void
    {
        $result = (new RiskGuardService())->evaluate([
            'kill_switch_active' => true,
        ]);

        $this->assertFalse($result->approved);
        $this->assertSame('block', $result->decision);
        $this->assertContains('kill_switch_active', $result->blockedBy);
        $this->assertContains('blocked_by_kill_switch', $result->reasonCodes);
    }

    public function test_risk_guard_blocks_on_critical_portfolio_heat(): void
    {
        $result = (new RiskGuardService())->evaluate([
            'portfolio_heat' => 'critical',
        ]);

        $this->assertFalse($result->approved);
        $this->assertContains('portfolio_heat_critical', $result->blockedBy);
        $this->assertContains('blocked_by_portfolio_heat', $result->reasonCodes);
    }

    public function test_risk_guard_warns_on_high_heat_and_bad_liquidity(): void
    {
        $result = (new RiskGuardService())->evaluate([
            'portfolio_heat' => 'high',
            'liquidity_ok' => false,
        ]);

        $this->assertTrue($result->approved);
        $this->assertSame('allow', $result->decision);
        $this->assertContains('portfolio_heat_high', $result->warnings);
        $this->assertContains('liquidity_not_ok', $result->warnings);
    }

    public function test_thesis_strength_calculates_strong_context(): void
    {
        $score = (new ThesisStrengthService())->calculate([
            'price_structure_score' => 90,
            'volume_confirmation_score' => 85,
            'momentum_score' => 80,
            'regime_fit' => 85,
            'news_risk_score' => 10,
            'execution_quality' => 80,
        ]);

        $this->assertGreaterThanOrEqual(80, $score);
    }

    public function test_thesis_strength_calculates_weak_context(): void
    {
        $score = (new ThesisStrengthService())->calculate([
            'price_structure_score' => 25,
            'volume_confirmation_score' => 30,
            'momentum_score' => 25,
            'regime_fit' => 35,
            'news_risk_score' => 90,
            'execution_quality' => 30,
        ]);

        $this->assertLessThan(40, $score);
    }

    public function test_trade_lifecycle_detects_continuation_state(): void
    {
        $result = (new TradeLifecycleService())->evaluate([
            'price_structure_score' => 90,
            'volume_confirmation_score' => 85,
            'momentum_score' => 80,
            'regime_fit' => 85,
            'news_risk_score' => 10,
            'execution_quality' => 80,
        ]);

        $this->assertSame(ThesisState::Continuation, $result->state);
        $this->assertContains('hold_watch', $result->recommendations);
    }

    public function test_trade_lifecycle_detects_compression_state(): void
    {
        $result = (new TradeLifecycleService())->evaluate([
            'compression_detected' => true,
            'price_structure_score' => 55,
            'volume_confirmation_score' => 55,
            'momentum_score' => 45,
            'regime_fit' => 50,
            'news_risk_score' => 50,
            'execution_quality' => 50,
        ]);

        $this->assertSame(ThesisState::Compression, $result->state);
        $this->assertContains('compression_detected', $result->warnings);
        $this->assertContains('compression_watch', $result->recommendations);
    }

    public function test_trade_compression_detects_compressed_conditions(): void
    {
        $result = (new TradeCompressionService())->analyze([
            'momentum_score' => 30,
            'volatility_score' => 35,
            'range_contraction_score' => 80,
            'volume_fade_score' => 75,
        ]);

        $this->assertTrue($result['compression_detected']);
        $this->assertContains('momentum_compressed', $result['reason_codes']);
        $this->assertContains('volume_fade_high', $result['reason_codes']);
    }

    public function test_adaptive_exit_holds_strong_trade(): void
    {
        $action = (new AdaptiveExitService())->evaluate([
            'price_structure_score' => 90,
            'volume_confirmation_score' => 85,
            'momentum_score' => 80,
            'regime_fit' => 85,
            'news_risk_score' => 10,
            'execution_quality' => 80,
        ]);

        $this->assertSame(AiRecommendedAction::HoldTrade->value, $action);
    }

    public function test_adaptive_exit_reduces_risk_on_compression(): void
    {
        $action = (new AdaptiveExitService())->evaluate([
            'momentum_score' => 30,
            'volatility_score' => 35,
            'range_contraction_score' => 80,
            'volume_fade_score' => 75,
            'price_structure_score' => 65,
            'volume_confirmation_score' => 60,
            'regime_fit' => 60,
            'news_risk_score' => 30,
            'execution_quality' => 60,
        ]);

        $this->assertSame(AiRecommendedAction::ReduceRisk->value, $action);
    }

    public function test_adaptive_exit_flags_exit_on_exhaustion(): void
    {
        $action = (new AdaptiveExitService())->evaluate([
            'exhaustion_detected' => true,
        ]);

        $this->assertSame(AiRecommendedAction::ExitTrade->value, $action);
    }
}

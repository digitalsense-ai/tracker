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
use App\Services\Trading\AdaptiveExitService;
use App\Services\Trading\ThesisStrengthService;
use App\Services\Trading\TradeCompressionService;
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

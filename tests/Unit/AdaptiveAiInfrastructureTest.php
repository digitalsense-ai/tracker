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

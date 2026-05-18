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

    public function test_trade_lifecycle_defaults_to_unknown_state(): void
    {
        $result = (new TradeLifecycleService())->evaluate();

        $this->assertSame(ThesisState::Unknown, $result->state);
        $this->assertSame(0, $result->thesisStrength);
    }
}

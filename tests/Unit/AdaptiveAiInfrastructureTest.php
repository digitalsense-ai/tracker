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
use App\Services\AI\RegimeDriftDetectionService;
use App\Services\AI\StrategyDriftDetectionService;
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

    public function test_confidence_calibration_detects_overestimation(): void
    {
        $result = (new ConfidenceCalibrationService())->analyze([
            'average_confidence' => 90,
            'realized_quality' => 55,
            'sample_size' => 50,
        ]);

        $this->assertSame('calibration_drift_high', $result['status']);
        $this->assertSame(35, $result['calibration_gap']);
        $this->assertSame('review_confidence_overestimation', $result['recommendation']);
        $this->assertContains('confidence_overestimated', $result['reason_codes']);
    }

    public function test_confidence_calibration_requires_sufficient_sample(): void
    {
        $result = (new ConfidenceCalibrationService())->analyze([
            'average_confidence' => 90,
            'realized_quality' => 55,
            'sample_size' => 5,
        ]);

        $this->assertSame('insufficient_sample', $result['status']);
        $this->assertSame('collect_more_samples', $result['recommendation']);
        $this->assertContains('sample_size_low', $result['reason_codes']);
    }

    public function test_strategy_drift_detects_win_rate_and_avg_r_degradation(): void
    {
        $result = (new StrategyDriftDetectionService())->analyze([
            'baseline_win_rate' => 75,
            'recent_win_rate' => 45,
            'baseline_avg_r' => 80,
            'recent_avg_r' => 50,
            'false_positive_rate' => 45,
            'sample_size' => 60,
        ]);

        $this->assertTrue($result['drift_detected']);
        $this->assertSame('strategy_drift_high', $result['status']);
        $this->assertSame('review_strategy_degradation', $result['recommendation']);
        $this->assertContains('win_rate_drift_high', $result['reason_codes']);
        $this->assertContains('avg_r_drift_high', $result['reason_codes']);
        $this->assertContains('false_positive_rate_high', $result['reason_codes']);
    }

    public function test_execution_drift_detects_slippage_and_spread_drift(): void
    {
        $result = (new ExecutionDriftDetectionService())->analyze([
            'baseline_slippage' => 1.0,
            'recent_slippage' => 2.0,
            'baseline_spread' => 0.5,
            'recent_spread' => 1.0,
        ]);

        $this->assertTrue($result['drift_detected']);
        $this->assertSame('review_execution_quality', $result['recommendation']);
        $this->assertContains('slippage_drift_detected', $result['reason_codes']);
        $this->assertContains('spread_drift_detected', $result['reason_codes']);
    }

    public function test_regime_drift_detects_instability_and_misclassification(): void
    {
        $result = (new RegimeDriftDetectionService())->analyze([
            'regime_stability' => 45,
            'misclassification_rate' => 35,
        ]);

        $this->assertTrue($result['drift_detected']);
        $this->assertSame('review_regime_classification', $result['recommendation']);
        $this->assertContains('regime_instability_detected', $result['reason_codes']);
        $this->assertContains('regime_misclassification_high', $result['reason_codes']);
    }

    public function test_ai_reasoning_drift_detects_degradation(): void
    {
        $result = (new AiReasoningDriftService())->analyze([
            'warning_effectiveness' => 45,
            'disagreement_score' => 70,
            'reason_code_instability' => 50,
        ]);

        $this->assertTrue($result['drift_detected']);
        $this->assertSame('review_ai_reasoning_quality', $result['recommendation']);
        $this->assertContains('warning_effectiveness_degraded', $result['reason_codes']);
        $this->assertContains('ai_disagreement_increasing', $result['reason_codes']);
        $this->assertContains('reason_code_instability_detected', $result['reason_codes']);
    }

    public function test_trade_lifecycle_defaults_to_unknown_state(): void
    {
        $result = (new TradeLifecycleService())->evaluate();

        $this->assertSame(ThesisState::Unknown, $result->state);
        $this->assertSame(0, $result->thesisStrength);
    }
}

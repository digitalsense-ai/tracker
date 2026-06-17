<?php

namespace App\Services\AI;

use App\Services\Risk\PortfolioRiskService;
use App\Services\Risk\RiskGuardService;
use App\Services\Trading\AdaptiveExitService;
use App\Services\Trading\TradeLifecycleService;

class AdaptiveAiOrchestratorService
{
    public function __construct(
        private readonly AiRealityCheckService $realityCheckService = new AiRealityCheckService(),
        private readonly MarketRegimeService $marketRegimeService = new MarketRegimeService(),
        private readonly ConfidenceEngineService $confidenceEngineService = new ConfidenceEngineService(),
        private readonly PortfolioRiskService $portfolioRiskService = new PortfolioRiskService(),
        private readonly RiskGuardService $riskGuardService = new RiskGuardService(),
        private readonly TradeLifecycleService $tradeLifecycleService = new TradeLifecycleService(),
        private readonly AdaptiveExitService $adaptiveExitService = new AdaptiveExitService(),
        private readonly MetaAiSupervisorService $metaAiSupervisorService = new MetaAiSupervisorService(),
        private readonly ExecutionQualityAiService $executionQualityService = new ExecutionQualityAiService(),
    ) {
    }

    /**
     * Observe-only adaptive AI orchestrator v1.
     *
     * Aggregates all adaptive AI modules into one explainable snapshot.
     */
    public function orchestrate(array $context = []): array
    {
        $reality = $this->realityCheckService->evaluate($context);
        $regime = $this->marketRegimeService->detect($context);
        $confidence = $this->confidenceEngineService->calculate($context);
        $portfolioRisk = $this->portfolioRiskService->analyze($context);
        $riskGuard = $this->riskGuardService->evaluate([
            ...$context,
            'portfolio_heat' => strtolower($portfolioRisk->heatLevel->value),
        ]);
        $tradeLifecycle = $this->tradeLifecycleService->evaluate($context);
        $adaptiveExit = $this->adaptiveExitService->evaluate($context);
        $executionQuality = $this->executionQualityService->evaluate($context);

        $metaSupervisor = $this->metaAiSupervisorService->evaluate([
            'confidence_health' => $confidence->confidence,
            'regime_stability' => $regime->confidence,
            'portfolio_health' => 100 - $portfolioRisk->riskScore,
            'execution_health' => $executionQuality->qualityScore,
            'model_health' => 100,
            'module_disagreement_score' => $this->disagreementScore(
                $confidence->confidence,
                $regime->confidence,
                $executionQuality->qualityScore,
            ),
        ]);

        return [
            'adaptive_state' => [
                'reality_check' => $reality,
                'market_regime' => $regime,
                'confidence' => $confidence,
                'portfolio_risk' => $portfolioRisk,
                'risk_guard' => $riskGuard,
                'trade_lifecycle' => $tradeLifecycle,
                'adaptive_exit' => $adaptiveExit,
                'execution_quality' => $executionQuality,
                'meta_supervisor' => $metaSupervisor,
            ],
            'summary' => [
                'system_health' => $metaSupervisor->systemHealth,
                'requires_attention' => $metaSupervisor->requiresAttention,
                'portfolio_heat' => $portfolioRisk->heatLevel->value,
                'confidence_level' => $confidence->level,
                'trade_state' => $tradeLifecycle->state->value,
                'adaptive_exit_recommendation' => $adaptiveExit,
            ],
        ];
    }

    private function disagreementScore(int $confidence, int $regime, int $execution): int
    {
        $values = [$confidence, $regime, $execution];

        return max($values) - min($values);
    }
}

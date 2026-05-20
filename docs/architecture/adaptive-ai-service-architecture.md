# Adaptive AI Trading Infrastructure — Service Architecture Map

## Purpose

This document maps the proposed Laravel service structure for the adaptive AI trading infrastructure.

It is intentionally architectural only. The first implementation phase should create skeletons, contracts, DTOs, config, and observe-only logging before any module can affect live trading.

## Design Rule

```text
Services decide and explain.
Risk guards approve or reject.
Activation modes control whether actions are allowed.
Logs preserve every recommendation.
```

## Proposed Folder Structure

```text
app/
  Services/
    AI/
      AiRealityCheckService.php
      ConfidenceEngineService.php
      MarketRegimeService.php
      NewsMacroAiService.php
      MarketNarrativeService.php
      StrategySelectorAiService.php
      SetupQualityAiService.php
      MetaAiSupervisorService.php
      ModelDriftMonitorService.php
      ExecutionQualityAiService.php
      PerformanceAttributionAiService.php
      StrategyOptimizerAiService.php

    Risk/
      RiskGuardService.php
      PortfolioRiskService.php
      PortfolioHeatService.php
      KillSwitchService.php
      OpportunityCostService.php
      CapitalAllocationService.php

    Trading/
      LiveLoopExecutorService.php
      TradeLifecycleService.php
      PositionReclassificationService.php
      ThesisStrengthService.php
      DynamicPositionScalingService.php
      AdaptiveExitService.php
      TradeCompressionService.php

  DTO/
    AI/
      AiDecisionResult.php
      RealityCheckResult.php
      ConfidenceResult.php
      RegimeResult.php
      NewsRiskResult.php
      PortfolioRiskResult.php
      RiskGuardResult.php
      MetaAiSupervisorResult.php
      ExecutionQualityResult.php
      TradeLifecycleResult.php
      PositionReclassificationResult.php

  Enums/
    AI/
      ActivationMode.php
      AiRecommendedAction.php
      MarketRegime.php
      ConfidenceLevel.php
      ThesisState.php
      PortfolioHeatLevel.php

config/
  ai_trading.php
```

## Activation Mode Enum

Recommended values:

```text
observe_only
assistive
controlled_automation
disabled
```

## Recommended Action Enum

Recommended values:

```text
continue
downgrade_candidate
remove_candidate
pause_model
reduce_risk
force_replan
emergency_stop
exit_trade
hold_trade
scale_down
scale_up
reclassify_position
```

Not every module may use every action. Risk rules and activation modes decide which actions can be executed.

## Core Service Responsibilities

## AiRealityCheckService

Purpose:

Validate whether the original AI plan still fits current market reality.

Inputs:

- model
- candidate
- trade
- latest market data
- news state
- regime state
- liquidity/spread state

Output:

```text
RealityCheckResult
```

Initial mode:

```text
observe_only
```

Allowed initial behavior:

- log recommendation
- adjust confidence in logs
- display warnings

Disallowed initial behavior:

- exit trades
- remove candidates
- change live risk
- pause models automatically

## ConfidenceEngineService

Purpose:

Estimate the confidence and uncertainty of candidates, trades, plans, and AI decisions.

Inputs:

- setup score
- volume state
- spread state
- regime fit
- news risk
- historical strategy performance
- recent model performance

Output:

```text
ConfidenceResult
```

## MarketRegimeService

Purpose:

Classify market environment.

Initial regimes:

```text
trend
range
expansion
compression
risk_on
risk_off
news_driven
unknown
```

Output:

```text
RegimeResult
```

## NewsMacroAiService

Purpose:

Score market and instrument-level news/macro risk.

Outputs:

- news risk score
- macro risk score
- event risk flag
- headline severity
- recommended caution level

## StrategySelectorAiService

Purpose:

Choose which strategies should be active or weighted higher under current conditions.

Initial output should be recommendations only.

## SetupQualityAiService

Purpose:

Score how clean and valid a setup is.

Checks:

- setup structure
- breakout/retest quality
- fakeout risk
- continuation probability
- regime fit
- volume confirmation

## PortfolioRiskService

Purpose:

Prevent hidden concentration risk.

Checks:

- sector exposure
- correlated symbols
- strategy clustering
- directional exposure
- total open risk

Output:

```text
PortfolioRiskResult
```

## PortfolioHeatService

Purpose:

Measure total system stress.

Inputs:

- volatility
- correlation
- drawdown
- spread degradation
- open risk
- news risk
- execution quality
- confidence collapse

Output:

```text
low | medium | high | critical
```

## RiskGuardService

Purpose:

Central rule-based safety service.

Hard rules must override AI.

Checks:

- max daily loss
- max model loss
- max trade risk
- max consecutive losses
- kill switch state
- allowed activation mode
- module-specific safety rules

## KillSwitchService

Purpose:

Control manual and automatic stop conditions.

Types:

```text
system_kill_switch
model_kill_switch
strategy_kill_switch
symbol_kill_switch
broker_kill_switch
```

## MetaAiSupervisorService

Purpose:

Overlook all AI modules and system state.

Responsibilities:

- detect disagreement between AI modules
- detect confidence collapse
- detect model drift
- detect strategy degradation
- detect overexposure
- detect execution degradation
- recommend replan or risk reduction

Initial mode:

```text
observe_only
```

## ModelDriftMonitorService

Purpose:

Detect whether a strategy/model is behaving worse than expected.

Checks:

- recent win rate vs baseline
- average R vs baseline
- drawdown vs baseline
- execution degradation
- regime mismatch
- confidence calibration error

## TradeLifecycleService

Purpose:

Track open trade state as a live thesis instead of a static entry.

Lifecycle states:

```text
discovery
validation
expansion
continuation
compression
exhaustion
exit
```

## ThesisStrengthService

Purpose:

Maintain a live thesis strength score from 0 to 100.

Example:

```text
Entry thesis = 92
Volume fades = 76
Market weakens = 61
Below threshold = reduce or exit recommendation
```

## PositionReclassificationService

Purpose:

Check whether a trade that no longer fits its original strategy now fits a stronger compatible strategy.

Safety rules:

```text
Max 1 transition per trade
Only same-direction transitions
New thesis must improve expected value
Hard stop can never widen
No averaging down
High confidence required
```

Initial mode:

```text
observe_only
```

## DynamicPositionScalingService

Purpose:

Recommend scaling up/down as confidence changes.

Initial mode:

```text
observe_only
```

## AdaptiveExitService

Purpose:

Recommend exits based on current trade quality, not only fixed TP/SL.

Examples:

```text
Strong trend = hold longer
Weak momentum = take profit earlier
Compression = exit or reduce
Regime shift = reduce or exit
```

## ExecutionQualityAiService

Purpose:

Measure whether the system is realizing edge during execution.

Metrics:

- expected entry
- actual entry
- slippage
- spread at entry
- fill quality
- timing quality

## PerformanceAttributionAiService

Purpose:

Explain why strategies and trades are winning or losing.

Dimensions:

- setup type
- regime
- news state
- confidence level
- time of day
- symbol
- sector
- execution quality

## StrategyOptimizerAiService

Purpose:

Suggest improvements to filters, strategy weights, time windows, and parameters.

Important:

This service should never self-deploy changes. Suggestions require review.

## Recommended Config Structure

```php
return [
    'default_activation_mode' => 'observe_only',

    'modules' => [
        'reality_check' => [
            'enabled' => true,
            'activation_mode' => 'observe_only',
            'interval_minutes' => 60,
        ],
        'confidence_engine' => [
            'enabled' => true,
            'activation_mode' => 'observe_only',
        ],
        'market_regime' => [
            'enabled' => true,
            'activation_mode' => 'observe_only',
        ],
        'portfolio_risk' => [
            'enabled' => false,
            'activation_mode' => 'observe_only',
        ],
        'meta_ai_supervisor' => [
            'enabled' => false,
            'activation_mode' => 'observe_only',
        ],
    ],

    'safety' => [
        'require_observe_only_before_activation' => true,
        'allow_ai_to_widen_hard_stop' => false,
        'allow_ai_to_average_down' => false,
        'allow_ai_to_self_deploy_changes' => false,
    ],
];
```

## Proposed Artisan Commands

```text
ai:reality-check
ai:calculate-confidence
ai:detect-regime
ai:portfolio-risk
ai:meta-supervisor
ai:drift-monitor
ai:performance-attribution
```

Initial commands should log only.

## Proposed Logging Tables

```text
ai_decision_logs
ai_reality_checks
ai_confidence_snapshots
ai_regime_snapshots
ai_news_risk_snapshots
ai_portfolio_risk_snapshots
ai_risk_events
ai_supervisor_events
ai_execution_quality_snapshots
ai_trade_lifecycle_snapshots
```

## Review Checklist Before Any Live Integration

Before a module can affect live trading, ServerAdmin should verify:

```text
Has it run in observe-only mode?
Are outputs stored and reviewable?
Are risk rules enforced outside AI?
Can the module be disabled from config?
Does it have safe defaults?
Does it avoid widening hard stops?
Does it avoid averaging down?
Does it avoid self-deploying changes?
Are recommendations explainable?
```

## Final Rule

```text
AI can recommend.
RiskGuard approves.
ActivationMode permits.
KillSwitch can always block.
```

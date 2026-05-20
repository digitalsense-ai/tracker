# Adaptive AI Trading Infrastructure — Risk and Safety Rules

## Purpose

This document defines the non-negotiable safety rules for the adaptive AI trading infrastructure.

The system may become more intelligent over time, but safety must remain deterministic, reviewable, and enforceable outside the AI layer.

## Core Safety Philosophy

```text
AI may recommend.
RiskGuard must approve.
ActivationMode must permit.
KillSwitch can always block.
Human/Admin review controls activation.
```

AI should never be the final authority over capital risk.

## Activation Modes

Every AI module must run under one activation mode.

### Disabled

The module does not run.

### Observe Only

The module may:

- analyze
- score
- log
- explain
- recommend
- display warnings

The module may not:

- change candidates
- change trades
- change risk
- change orders
- pause models
- execute exits

### Assistive

The module may:

- recommend actions
- flag candidates
- downgrade candidates
- request replans
- request risk reduction
- request model pause

The module may not execute final live actions unless another approved system component accepts the recommendation.

### Controlled Automation

The module may trigger approved actions only when:

- activation mode permits it
- RiskGuard approves it
- KillSwitch is not active
- action is within configured limits
- action is logged
- action is explainable

## Mandatory Observe-Only Rule

No AI module may affect live trading until it has:

1. Run in observe-only mode.
2. Produced reviewable logs.
3. Been compared against actual outcomes.
4. Passed defined safety thresholds.
5. Been explicitly approved for activation.

## Non-Negotiable AI Prohibitions

AI must never be allowed to:

```text
Remove hard stops
Widen maximum trade risk automatically
Ignore kill switches
Auto-deploy untested strategies
Average down losing trades
Reclassify trades endlessly
Self-modify core risk logic
Increase leverage outside limits
Override broker/execution safety
Hide or delete decision logs
```

## Hard Risk Rules

These rules should live outside the AI layer and override all AI recommendations.

Recommended hard controls:

```text
Max daily loss
Max weekly loss
Max model loss
Max strategy loss
Max symbol exposure
Max sector exposure
Max correlated exposure
Max open trades
Max position size
Max consecutive losses
Max slippage
Max spread
Max portfolio heat
Manual kill switch
Automatic kill switch
```

## Kill Switch Rules

The system should support multiple kill switch levels.

```text
system_kill_switch
model_kill_switch
strategy_kill_switch
symbol_kill_switch
broker_kill_switch
```

### Manual Kill Switch

A human/Admin can pause:

- the whole system
- a model
- a strategy
- a symbol
- execution

### Automatic Kill Switch

Automatic safety triggers may include:

```text
Max daily loss reached
Abnormal spread expansion
Liquidity collapse
Execution failure
Broker/API error spike
Unexpected position state
Portfolio heat critical
AI confidence collapse
Model drift critical
Repeated failed exits
News shock detected
```

Initial automatic kill switch behavior should be observe-only or assistive until reviewed.

## Position Reclassification Safety Rules

Position reclassification is powerful but dangerous if it becomes a way to avoid taking losses.

Mandatory rules:

```text
Max 1 transition per trade
Only same-direction transitions
New thesis must improve expected value
New thesis confidence must exceed threshold
Hard stop can never widen
No averaging down
No transition after hard invalidation
No transition if kill switch risk is active
All transitions must be logged
```

Recommended first allowed transition:

```text
ORB Retest Long → Momentum Continuation Long
```

Do not initially allow broad strategy hopping.

## Dynamic Position Scaling Safety Rules

Scaling should be conservative.

AI may not:

```text
Scale beyond max position size
Scale up after thesis invalidation
Scale up while portfolio heat is high
Scale up during kill switch warning
Scale up to average down a loser
```

Initial implementation should recommend only.

## Adaptive Exit Safety Rules

Adaptive exits may improve profitability, but they must not remove protection.

AI may recommend:

```text
hold
reduce
exit early
trail tighter
take partial profit
```

AI may not:

```text
remove stop loss
widen hard stop beyond original risk
ignore time stop
ignore emergency exit rules
```

## Confidence Engine Safety Rules

Confidence must not be treated as certainty.

The Confidence Engine should output:

```text
confidence_score
uncertainty_score
confidence_level
reason_codes
```

Low confidence should generally reduce aggressiveness.

High confidence should not override hard risk limits.

## Meta-AI Supervisor Safety Rules

The Meta-AI Supervisor overlooks the system but should not start as a controller.

Rollout stages:

```text
Stage 1: observe and log warnings
Stage 2: recommend risk reductions or replans
Stage 3: trigger approved safety actions
Stage 4: limited controlled automation under hard rules
```

The Meta-AI Supervisor may never override RiskGuard or KillSwitch.

## Logging Requirements

Every AI decision or recommendation should be logged with:

```text
module_name
activation_mode
input_summary
output_action
confidence_score
uncertainty_score
reason_codes
risk_guard_result
was_action_executed
created_at
```

For live-affecting actions, also log:

```text
before_state
after_state
approved_by
execution_source
```

## Explainability Requirements

AI decisions should include reason codes where possible.

Example reason codes:

```text
regime_mismatch
news_risk_increased
spread_expanded
volume_faded
confidence_declined
portfolio_heat_high
correlation_risk_high
setup_invalidated
thesis_transition_detected
execution_quality_degraded
```

## ServerAdmin Review Checklist

Before any AI module can move beyond observe-only, ServerAdmin should review:

```text
Does the module have config flags?
Can it be disabled quickly?
Has it logged enough observe-only data?
Are recommendations explainable?
Are hard risk rules enforced outside AI?
Can KillSwitch block the module?
Does it avoid widening hard stops?
Does it avoid averaging down?
Does it avoid strategy hopping?
Does it preserve logs?
Does it have safe defaults?
```

## Deployment Rule

New AI modules should be deployed in this order:

```text
Local/dev
↓
Staging
↓
Observe-only production
↓
Reviewed assistive mode
↓
Small controlled automation
↓
Broader controlled automation
```

## Final Safety Principle

The system should become more adaptive over time, but not less controlled.

```text
More AI requires more safety, not less.
```

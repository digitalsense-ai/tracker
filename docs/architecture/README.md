# Tracker Architecture Docs

## Adaptive AI Trading Infrastructure

This folder contains the planning, safety, and rollout documentation for the adaptive AI trading infrastructure foundation.

Recommended reading order:

1. [Adaptive AI Trading Blueprint](adaptive-ai-trading-blueprint.md)
2. [Implementation Roadmap](adaptive-ai-implementation-roadmap.md)
3. [Service Architecture Map](adaptive-ai-service-architecture.md)
4. [Risk and Safety Rules](adaptive-ai-risk-and-safety-rules.md)
5. [Observe-Only Scheduling Plan](adaptive-ai-observe-only-scheduling.md)
6. [ServerAdmin Handoff Checklist](adaptive-ai-serveradmin-handoff.md)

## Current Rollout Stage

```text
Foundation architecture
↓
Observe-only logging
↓
Manual observe-only commands
↓
Staging review
↓
Future assistive mode
↓
Future controlled automation
```

## Important Safety Note

The current foundation is designed to be non-invasive.

It should not:

- change live trades
- change entries
- change exits
- change broker behavior
- change strategy behavior
- automate AI decisions

The next recommended step is ServerAdmin review and staging validation before scheduling or live-loop integration.

# Tracker
**Version: 0.9.0-beta**

Tracker is a Laravel-based trading research and operations platform for strategy development, simulation, monitoring, model workflows, performance analysis, and review.

It brings together the major parts of a trading system into one application:

- strategy configuration
- signal exploration
- backtesting and simulation
- results and KPI analysis
- model and prompt workflows
- kanban-based planning and review
- profiles and leaderboard comparisons
- trade reviews and feedback summaries
- live monitoring
- broker integration entry points

Tracker is no longer best described as only a single AI trading loop.  
It is better understood as a broader platform for building, observing, reviewing, and improving trading systems.

---

## Vision

The purpose of Tracker is to provide a single workspace where trading logic, system behavior, and outcomes can be managed end to end.

Instead of splitting this work across scripts, spreadsheets, dashboards, notes, and broker tools, Tracker aims to keep the workflow in one place.

The platform is intended to support both:

- **systematic trading workflows**, where signals, rules, and simulations are central
- **model-assisted workflows**, where AI models, prompts, logs, and structured review are part of the process

At a practical level, Tracker is built to help answer questions like:

- What strategy settings are active right now?
- What signals or opportunities are appearing?
- How has the strategy performed historically?
- What is the current win rate, average R, and net result?
- Which model or profile is performing best?
- What happened in a specific trade?
- What patterns appear in feedback and review?
- Is the system healthy and operational?
- Are broker connections and integrations ready?

---

## System Overview

Tracker is designed around several connected layers.

### 1. Market data layer
This layer is responsible for supplying market prices, candles, and market/session-aware data to the rest of the platform.

Expected responsibilities:
- fetch and normalize market data
- support one or more data providers
- handle ticker and symbol formatting
- support session logic and market timing
- feed dashboards, signals, backtests, and live views

### 2. Strategy and signal layer
This layer turns strategy definitions and market data into candidate setups and signals.

Expected responsibilities:
- define configurable strategy rules
- support breakout/retest and similar logic
- apply filters and confirmation rules
- generate candidate signals
- expose setup information to UI and simulations

### 3. Simulation and backtest layer
This layer evaluates strategy behavior historically and stores trade outcomes.

Expected responsibilities:
- replay strategy logic on historical data
- simulate entries, exits, stops, and targets
- account for fees and slippage assumptions
- calculate PnL and R-multiples
- support parameter tuning and iteration

### 4. Results and analytics layer
This layer turns stored trade data into useful reporting.

Expected responsibilities:
- filter historical or simulated trades
- calculate KPIs such as win rate, average R, and net profit
- support exports and reporting views
- power trade results and summary pages

### 5. Model and workflow layer
This layer manages models, prompts, logs, and workflow surfaces.

Expected responsibilities:
- manage model entities and settings
- expose prompts and model logs
- support model-specific chats and inspection
- support kanban-style workflow tracking
- make model behavior easier to compare and improve

### 6. Review and feedback layer
This layer handles the learning loop after trades and simulations.

Expected responsibilities:
- review completed trades
- summarize feedback patterns
- support retrospective analysis
- help improve strategy and model behavior over time

### 7. Live monitoring and integration layer
This layer supports operational monitoring and future broker-connected workflows.

Expected responsibilities:
- show live or near-live system state
- support operational monitoring
- surface integration readiness
- connect to broker authentication and execution infrastructure

---

## How the platform fits together

A simple mental model for Tracker is:

```text
Market Data
  ↓
Strategy Rules / Signal Logic
  ↓
Backtest / Simulation / Candidate Trades
  ↓
Trade Storage / KPIs / Results
  ↓
Model Workflows / Reviews / Feedback
  ↓
Dashboards / Live Monitoring / Comparison
  ↓
Broker Integration / Future Execution
````

This matters because Tracker is not only about generating trades.

It is also about understanding:

* why a setup appeared
* how it performed
* how it compares with alternatives
* whether the system is healthy
* what should be improved next

That makes Tracker both an **analysis platform** and an **operations platform**.

---

## Main product areas

### Dashboard and status

These pages provide a high-level view of system behavior and health.

Purpose:

* show operational status
* expose strategy configuration
* help identify issues quickly
* provide a starting point for navigating the platform

Routes include:

* `/dashboard`
* `/status`

### KPI and trade results

These pages support performance analysis and reporting.

Purpose:

* inspect historical or simulated trades
* filter by date, ticker, or status
* calculate win rate, average R, fees, and net result
* export result data

Routes include:

* `/kpi`
* `/results`

### Backtest and signals

These pages support research, validation, and idea exploration.

Purpose:

* inspect strategy behavior
* review candidate trade opportunities
* evaluate whether signal logic is behaving correctly
* connect idea generation to measurable outcomes

Routes include:

* `/backtest`
* `/signals`

### Explainer surfaces

These pages appear intended to make system behavior more understandable.

Purpose:

* explain process flow
* support onboarding and debugging
* improve transparency around strategy or model behavior

Routes include:

* `/explainer`
* `/explainer-flow`

### Settings and configuration

This area centralizes application and strategy-related configuration.

Purpose:

* manage settings
* control runtime behavior
* support environment and strategy adjustments

Routes include:

* `/settings`

### Models

The model layer is one of the largest parts of the application.

Purpose:

* manage model entities
* compare variants
* expose model-specific pages and workflows
* support iteration and transparency around model behavior

Routes include:

* `/models`
* `/models/create`
* `/models/{model}/edit`
* `/models/{slug}`

### Model workflow surfaces

These surfaces provide deeper visibility and process control for each model.

Purpose:

* inspect logs and prompts
* interact with model-specific views
* track work in kanban stages
* support debugging and refinement

Routes include:

* `/models/{slug}/kanban`
* `/models/{slug}/kanban-v2`
* `/models/{slug}/chat`
* `/models/{slug}/log`
* `/models/{slug}/prompt/{prompt}`

### Profiles and leaderboard

These areas support comparison between profiles, models, or configurations.

Purpose:

* rank performance
* compare variants
* observe stronger and weaker approaches
* organize multiple strategy identities

Routes include:

* `/profiles/leaderboard`
* `/profiles/{slug}`
* `/leaderboard`

### Live monitoring

This is the operational runtime layer.

Purpose:

* observe current platform state
* bridge analysis and live monitoring
* support future real-time workflows

Routes include:

* `/live`

### Trade review and feedback

This is the post-trade learning layer.

Purpose:

* inspect completed trades
* review outcomes in detail
* summarize recurring strengths and weaknesses
* build a continuous improvement loop

Routes include:

* `/trade-reviews`
* `/trade-reviews/{tradeReview}`
* `/feedback-summaries`
* `/feedback-summaries/{aiModel}`

### Broker integration

This is the system boundary between internal workflows and external broker infrastructure.

Purpose:

* support broker authentication
* prepare for account and execution connectivity
* bridge internal logic with external APIs

Routes include:

* `/saxo/login`
* `/saxo/callback`

---

## What is already confirmed in the current codebase

Several parts of the system are already clearly visible in the repository.

### Trade results

The results controller works on `simulated_trades`, supports filtering, calculates KPIs, and allows CSV export.

This confirms the presence of:

* stored simulated trades
* reporting and filtering
* summary metrics
* export support

### KPI analytics

The KPI controller calculates:

* total trade rows
* wins
* losses
* closed trades
* win rate
* average R
* net profit

This confirms that the performance layer is metric-driven rather than only visual.

### Status monitoring

The status controller exposes:

* strategy configuration values
* datafeed status
* cron/run status
* broker readiness notes

This confirms the project already includes an operational health layer.

### Strategy configurability

The status layer references values such as:

* range minutes
* entry buffer percent
* retest requirement
* stop-loss buffer
* take-profit levels
* trailing stop
* session start/end
* position size
* fee settings
* datafeed settings

This strongly suggests the platform is built around configurable strategy logic.

---

## Current architecture vs legacy architecture

Earlier documentation described the project mainly as an AI execution engine with concepts such as:

* `AiDailyPlan`
* `ai:tick`
* `STATE`
* `AiDecisionParser`
* `PaperBroker`
* prompt-driven `OPEN`, `CLOSE`, and `HOLD` decisions

That may still reflect an older or partial layer of the project.

However, the current repository now exposes a much broader platform surface that includes:

* dashboards
* KPI analytics
* results reporting
* signals and backtests
* models and prompts
* kanban workflows
* profiles and leaderboards
* trade reviews
* feedback summaries
* live pages
* broker integration routes

For that reason, Tracker should now be described as a **trading research and operations platform**, not only as a single AI trading loop.

---

## Expected modules in the project

A complete version of Tracker should be expected to include these categories of modules.

### Core data modules

* market data services
* candle/session providers
* ticker normalization
* symbol metadata
* settings/configuration services

### Strategy modules

* range detection
* breakout/retest logic
* filters and confirmations
* stop and target calculation
* signal generation

### Simulation modules

* backtest engine
* trade simulator
* fee handling
* position sizing
* trade lifecycle handling

### Storage and analytics modules

* simulated trades
* performance snapshots
* KPI calculations
* result export
* reporting queries

### Model modules

* model records
* model settings
* prompt management
* model logs
* chat/history views
* kanban workflow surfaces

### Review modules

* trade review records
* feedback summaries
* annotations or reasoning notes
* evaluation workflows

### UI modules

* dashboard
* status page
* results page
* KPI page
* backtest page
* signals page
* live page
* explainer pages
* profile pages
* leaderboard pages

### Integration modules

* broker OAuth/authentication
* broker account connection
* datafeed selection
* cron or scheduled processing
* execution readiness monitoring

---

## Typical user workflows

### Research workflow

1. Configure strategy or model settings
2. Inspect signals and setups
3. Run or review backtests
4. Examine KPIs and results
5. Compare profiles or models

### Monitoring workflow

1. Open dashboard and status pages
2. Review system health
3. Inspect live monitoring surfaces
4. Confirm datafeed and scheduling activity
5. Verify broker integration readiness

### Review workflow

1. Inspect completed or simulated trades
2. Open review pages
3. Study feedback summaries
4. Identify recurring issues or strengths
5. Refine strategy or model settings

### Model workflow

1. Create or edit a model
2. Review kanban stage and workflow
3. Inspect model prompts, logs, and chat
4. Compare model behavior
5. Promote stronger variants

---

## Why this architecture matters

Tracker is valuable not only because it stores trades or shows signals.

Its real strength is that it creates a continuous loop across:

* strategy definition
* signal generation
* simulation
* monitoring
* analytics
* review
* comparison
* feedback
* iteration

That makes it much more useful than a standalone script, dashboard, or backtest tool.

Tracker is designed to become a workflow platform for improving trading systems over time.

---

## Tech stack

### Backend

* PHP 8.2
* Laravel 12
* Guzzle HTTP client

### Frontend

* Vite
* Tailwind CSS
* Alpine.js
* jQuery
* lightweight-charts

### Development tooling

The local development workflow is set up to run:

* Laravel dev server
* queue listener
* Laravel Pail
* Vite dev server

This suggests the platform is built for both normal web usage and background/process-driven behavior.

---

## Local setup

Clone the repository:

```bash
git clone https://github.com/digitalsense-ai/tracker.git
cd tracker
```

Install dependencies:

```bash
composer install
npm install
```

Create the environment file and generate the application key:

```bash
cp .env.example .env
php artisan key:generate
```

Run database migrations:

```bash
php artisan migrate
```

---

## Run locally

For the standard local development workflow:

```bash
composer run dev
```

This starts:

* the Laravel application server
* the queue listener
* Laravel Pail log tailing
* the Vite dev server

You can also run services individually:

```bash
php artisan serve
php artisan queue:listen --tries=1
npm run dev
```

---

## Build assets

```bash
npm run build
```

---

## Run tests

```bash
composer test
```

---

## Routing notes

The route layer appears to be under active evolution.

The current routing setup includes:

* many directly registered routes
* conditionally loaded split route files
* explicit `require` calls for route fragments
* debug and health helpers
* signs of route rescue/refactor history

This suggests the project has grown quickly and may still be consolidating architecture in some places.

---

## Versioning

Suggested current version:

**0.9.0-beta**

Why:

* the platform is already substantial
* several modules are integrated
* the architecture is meaningful and operational
* but there are still visible signs of restructuring and unfinished integration boundaries

Suggested progression:

* `0.9.x` for stabilization and cleanup
* `1.0.0` when routing, workflows, and integration boundaries are stable
* `1.1.x+` for more mature live and broker-connected features

---

## Future README improvements

This README can later be expanded with:

* folder structure overview
* architecture diagram
* screenshots of major pages
* environment variable documentation
* database notes
* datafeed setup instructions
* broker integration setup notes
* deployment instructions

---

## Summary

Tracker is a Laravel-based platform for:

* trading research
* strategy simulation
* signal exploration
* KPI and results analysis
* model and prompt workflows
* profile and leaderboard comparison
* trade review and feedback loops
* live monitoring
* broker integration readiness

It should be understood as a **trading research and operations platform** rather than only an AI trading engine.

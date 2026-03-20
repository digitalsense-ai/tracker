# 📊 AI Trading Tracker (ORB + LLM Decision Engine)

A Laravel-based trading system for **strategy analysis, simulation, and AI-driven decision making**, focused on Opening Range Breakout (ORB) and related intraday strategies.

This project combines:

* Market data + strategy logic
* AI-driven decision engine (LLM)
* Risk + execution simulation (paper trading)
* Full logging and model-based workflow

---

# 🚀 Overview

The system tracks intraday setups and uses configurable AI models to:

* Plan trading opportunities (pre-market)
* Evaluate setups at market open
* Make live trading decisions during the session
* Simulate trades with risk controls
* Log and analyze performance

It is designed to evolve toward a **fully autonomous trading system**.

---

# 🧠 Core Concepts

## AI Model

Each trading model is configurable and stored in `AiModel`.

It defines:

* Prompts:

  * `premarket_prompt`
  * `start_prompt`
  * `loop_prompt`
* Timing:

  * `premarket_run_time`
  * `check_interval_min`
  * `loop_min_price_move_pct`
* Risk constraints:

  * max strategies per day
  * max symbols per day
  * max adds per position
  * exposure / leverage / drawdown (future-ready)

👉 Think of an AI Model as a **self-contained trading brain**

---

## Trading Flow (CURRENT IMPLEMENTATION)

```text
Market Data / Scanner
→ Build STATE object
→ Loop Prompt (LLM)
→ AiDecisionParser
→ RiskManager
→ PaperBroker
→ Positions / Logs / Results
```

### Important

Although the system supports a 3-phase design (see below), the **current runtime is primarily loop-driven**.

---

## Target Architecture (DESIGNED FLOW)

```text
1. Pre-Market Planning
2. Session Start Activation
3. Intraday Loop Execution
```

### 1. Pre-Market Prompt

* Builds daily playbook
* Selects symbols + strategies
* Defines entry zones, invalidation, targets
* Stored in `AiDailyPlan`

### 2. Start Prompt (Open Phase)

* Validates pre-market ideas at open
* Activates or cancels setups
* Converts plan → actionable state

### 3. Loop Prompt (CORE ENGINE)

* Runs every N minutes
* Receives `STATE`
* Returns decision:

  * `OPEN`
  * `CLOSE`
  * `HOLD`

---

# ⚙️ Decision Flow

```text
STATE → Prompt → LLM → JSON → Parser → Risk → Execution
```

## Components

### STATE

Built from:

* open positions
* prices
* exposure
* model settings
* daily plan (future integration)

---

### Prompt (Loop)

Sent via `ResponsesClient`

Expected output:

```json
{
  "action": "OPEN|CLOSE|HOLD",
  "strategy": { "name": "string" },
  "reasoning": "string",
  "orders": []
}
```

---

### Parser (`AiDecisionParser`)

* Validates JSON
* Strips formatting
* Defaults to `HOLD` on failure
* Only allows:

  * `OPEN`
  * `CLOSE`
  * `HOLD`

---

### Risk Layer (`RiskManager`)

* Validates trades against model constraints
* Prevents invalid or excessive exposure

---

### Execution (`PaperBroker`)

* Simulates trades
* Updates positions
* Tracks PnL

---

### Persistence

* `Position` → open trades
* `Trade` → trade history
* `ModelLog` → AI decisions + payload
* `AiDailyPlan` → daily playbook

---

# 🧩 Project Structure

## Backend (Laravel)

```
app/
 ├── Models/
 │   ├── AiModel.php
 │   ├── AiDailyPlan.php
 │   ├── Position.php
 │   ├── Trade.php
 │   └── ModelLog.php
 │
 ├── Services/
 │   ├── StrategyEngine.php
 │   ├── ResponsesClient.php
 │   ├── AiDecisionParser.php
 │   ├── RiskManager.php
 │   ├── PaperBroker.php
 │   ├── PortfolioService.php
 │   └── BacktestService.php
 │
 ├── Http/Controllers/
 │   └── AI + tracker controllers
```

---

## Frontend

* Blade templates (`resources/views`)
* Tailwind CSS
* Alpine.js
* Lightweight Charts

---

## Routes

```
/models                → AI models
/models/{slug}         → model overview
/models/{slug}/prompt  → prompt editor
/models/{slug}/log     → decision logs
/models/{slug}/chat    → AI interaction

/dashboard             → tracker dashboard
/signals               → signals
/results               → results
/backtest              → backtesting
```

---

# 📚 Strategy Playbook Examples

These are example prompt/playbook styles that can be used per `AiModel`.

---

## 1. ORB Breakout

Use when:
- price is near the opening range boundary
- relative volume is elevated
- market context supports continuation

### Example idea
```json
{
  "symbol": "AAPL",
  "strategy": "orb_breakout",
  "direction": "LONG",
  "entry_zone_low": 182.10,
  "entry_zone_high": 182.80,
  "invalidation": 181.40,
  "target_1": 184.50,
  "target_2": 186.00,
  "priority": 1,
  "approved": true
}

# 🧠 Prompt Architecture (IMPORTANT)

The system is designed to separate concerns:

| Phase      | Purpose                    | Output              |
| ---------- | -------------------------- | ------------------- |
| Pre-Market | Strategy planning          | Daily playbook      |
| Start      | Activation filter          | Active setups       |
| Loop       | Execution + risk decisions | OPEN / CLOSE / HOLD |

---

## Loop Prompt Rules (CRITICAL)

* Must return valid JSON
* Must only use:

  * `OPEN`
  * `CLOSE`
  * `HOLD`
* No markdown / no extra text
* If invalid → system defaults to HOLD

---

# 📉 Strategy Scope

Supports multiple intraday strategies:

* Opening Range Breakout (ORB)
* Breakout + Retest
* Momentum
* VWAP Reversion
* Trend Following
* Gap & Go / Gap Fade

Strategy logic is enforced partly via:

* prompt design
* `StrategyEngine`
* model configuration

---

# 📊 Logging & Debugging

Every decision cycle can be tracked via:

* `ModelLog`
* positions
* trade history
* results views

This enables:

* prompt debugging
* strategy evaluation
* model comparison

---

# ⚠️ Known Limitations

* Loop prompt currently drives most execution
* Pre-market and start phases not fully enforced
* Parser only supports:

  * `OPEN`
  * `CLOSE`
  * `HOLD`
* No native support yet for:

  * scaling
  * partial exits
  * stop adjustments
* Routes folder contains legacy / backup files
* Some architecture still evolving

---

# 🛣 Roadmap

Planned improvements:

* Enforce full 3-phase architecture
* Expand parser:

  * `ADJUST`
  * `SCALE`
* Integrate `AiDailyPlan` into loop decisions
* Improve risk engine enforcement vs prompt
* Clean up routes and patch files
* Add real-time data integration
* Enable semi/fully automated execution

---

# ⚡ Development

## Requirements

* PHP 8.1+
* Laravel 10+
* Node (Vite)
* MySQL / MariaDB

## Setup

```bash
git clone https://github.com/digitalsense-ai/tracker.git
cd tracker

composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate
php artisan serve
```

---

# 🧪 Philosophy

This project is not just a tracker.

It is an attempt to build:

> A modular AI trading system where prompts, data, and execution form a closed feedback loop.

---

# 👨‍💻 Author Notes

* Prompts are as important as code
* Keep logic deterministic where possible
* Let AI decide *what*, not *how to execute*
* Always prioritize risk over opportunity

---

# 📌 Final Thought

The strength of this system comes from:

👉 alignment between:

* STATE
* PROMPTS
* PARSER
* RISK
* EXECUTION

When those align, the system becomes **predictable, testable, and scalable**.

# 📊 AI Trading Tracker

A Laravel-based AI trading engine for **intraday strategy execution, simulation, and decision automation**.

This system combines:

* AI-generated trade plans
* Real-time decision loops
* Structured state management
* Paper trading execution
* Full logging and evaluation

---

# 🚀 Overview

The system runs a **model-driven trading workflow**:

1. Pre-market generates a daily plan (`AiDailyPlan`)
2. Approved ideas become tradable (lane 3)
3. `ai:tick` runs on interval
4. AI evaluates current state
5. Decisions are parsed and executed via paper trading

---

# 🧠 Core Flow

```text
Pre-market → AiDailyPlan
→ Approved plan (lane 3)
→ ai:tick
→ Build STATE
→ loop_min_price_move_pct filter
→ OpenAI (LLM)
→ AiDecisionParser
→ Qty validation / clamp
→ PaperBroker
→ Positions / Trades / Logs / EquitySnapshot
```

---

# ⚙️ AiTick (Execution Engine)

`ai:tick` is the core runtime.

Each cycle:

* loads open positions and recent trades
* loads approved daily plan
* builds a rich `STATE`
* skips if market hasn’t moved enough
* calls OpenAI
* parses decision
* validates quantity
* executes via `PaperBroker`
* logs result and snapshots equity

---

# 📦 STATE Object

The AI receives a structured `STATE` JSON.

## Key fields

```json
{
  "time": "...",
  "session": {},
  "model": {},
  "account": {},
  "open_positions": [],
  "prices": {},
  "daily_plan": {},
  "watchlist": [],
  "market_context": {},
  "recent_actions": []
}
```

---

## session

```json
{
  "market": "US",
  "phase": "premarket|intraday|close",
  "minutes_since_open": 42
}
```

---

## model

Execution rules:

* check_interval_min
* loop_min_price_move_pct
* per_trade_alloc_pct
* max_exposure_pct
* max_drawdown_pct
* max_concurrent_trades
* cooldown_minutes
* max_adds_per_position

---

## account

```json
{
  "equity": 100000,
  "cash": 76000,
  "used_exposure_pct": 18.5,
  "day_pnl": 420.25,
  "drawdown_pct": 1.1
}
```

---

## daily_plan

```json
{
  "trade_date": "2026-03-20",
  "lane": 3,
  "approved_symbols": ["AAPL", "NVDA"],
  "items": []
}
```

---

## watchlist (core execution layer)

Each symbol includes:

* ticker
* last
* change_from_prev_loop_pct
* day_change_pct
* distance_to_entry_pct
* distance_to_vwap_pct
* relative_volume
* intraday_range_pct
* regime_hint

### Sizing fields

* entry_reference
* base_trade_budget
* max_qty
* allowed_size_multipliers

---

# 🧠 AI Decision Contract

The AI must return **pure JSON only**.

```json
{
  "action": "OPEN|CLOSE|HOLD",
  "strategy": "short description",
  "reasoning": "brief explanation",
  "orders": []
}
```

---

## Example OPEN

```json
{
  "action": "OPEN",
  "strategy": "AAPL breakout long",
  "reasoning": "Strong momentum and plan alignment",
  "orders": [
    {
      "symbol": "AAPL",
      "side": "BUY",
      "qty": 20,
      "type": "MARKET",
      "stop": 181.5,
      "target": 185.0
    }
  ]
}
```

---

## Example HOLD

```json
{
  "action": "HOLD",
  "strategy": "hold_existing",
  "reasoning": "No high-quality setup",
  "orders": []
}
```

---

# 🧩 Prompt Architecture

| Phase      | Purpose              |
| ---------- | -------------------- |
| Pre-market | Build daily playbook |
| Start      | Validate at open     |
| Loop       | Execute trades       |

👉 Current runtime is **loop-driven using approved plans**

---

# ✍️ Prompt Examples

## 🔹 System Prompt (CORE)

```text
You are an autonomous trading agent managing a single paper trading account.

You receive a STATE JSON object.
Use STATE as the single source of truth.

Your job is to return exactly one decision:
OPEN, CLOSE, or HOLD.

Rules:
- Only trade symbols in state.allowed_symbols
- Only open trades in state.daily_plan.approved_symbols
- Never open a symbol already in open_positions
- Always define stop and target
- Never exceed watchlist.max_qty
- Use allowed_size_multipliers
- If uncertain → HOLD

Return ONLY JSON.
```

---

## 🔹 Loop Prompt

```text
On each tick:

1. Review open_positions
- Close if stop, target, or invalidation is hit
- Close if thesis no longer valid

2. Review daily_plan + watchlist
- Only trade approved symbols
- Prefer plan-aligned setups

Use:
- change_from_prev_loop_pct
- day_change_pct
- distance_to_entry_pct
- distance_to_vwap_pct
- relative_volume
- intraday_range_pct
- regime_hint

3. OPEN only when:
- high-quality setup
- clear regime
- valid entry/stop/target
- qty within max_qty

4. Otherwise HOLD
```

---

## 🔹 Pre-Market Prompt

```text
You are my pre-market strategist.

Create today's playbook.

Return:
- approved_symbols
- setups
- entry zones
- invalidation levels
- targets
- priority

Focus on quality over quantity.
```

---

## 🔹 Start Prompt (optional)

```text
Validate today's plan at market open.

- keep valid setups
- cancel broken setups
- confirm active opportunities
```

---

# 📚 Strategy Playbook Examples

## 1. ORB Breakout

```json
{
  "symbol": "AAPL",
  "strategy": "orb_breakout",
  "direction": "LONG",
  "entry_zone_low": 182.10,
  "entry_zone_high": 182.80,
  "invalidation": 181.40,
  "target_1": 184.50,
  "target_2": 186.00
}
```

---

## 2. Momentum

```json
{
  "symbol": "NVDA",
  "strategy": "momentum",
  "direction": "LONG",
  "entry_zone_low": 918.00,
  "entry_zone_high": 922.00,
  "invalidation": 912.00,
  "target_1": 935.00
}
```

---

## 3. VWAP Reversion

```json
{
  "symbol": "TSLA",
  "strategy": "vwap_reversion",
  "direction": "SHORT",
  "entry_zone_low": 171.80,
  "entry_zone_high": 172.40,
  "invalidation": 173.20,
  "target_1": 169.90
}
```

---

## 4. Gap and Go

```json
{
  "symbol": "AMD",
  "strategy": "gap_and_go",
  "direction": "LONG",
  "entry_zone_low": 164.20,
  "entry_zone_high": 164.90,
  "invalidation": 163.40,
  "target_1": 166.80
}
```

---

## 5. Gap Fade

```json
{
  "symbol": "META",
  "strategy": "gap_fade",
  "direction": "SHORT",
  "entry_zone_low": 498.00,
  "entry_zone_high": 499.20,
  "invalidation": 500.40,
  "target_1": 493.50
}
```

---

# 📏 Sizing Logic

AI sees:

* base_trade_budget
* entry_reference
* max_qty
* allowed_size_multipliers

Backend enforces:

* qty ≤ max_qty
* invalid qty → adjusted or HOLD

---

# ⚡ loop_min_price_move_pct

Before running AI:

* compare current price vs last loop
* if movement < threshold → skip

Benefits:

* avoids noise
* reduces overtrading
* saves API cost

---

# 🧱 Core Components

| Component        | Role                 |
| ---------------- | -------------------- |
| AiTick           | Execution loop       |
| AiDecisionParser | JSON validation      |
| PaperBroker      | Trade execution      |
| ModelLog         | Logging              |
| EquitySnapshot   | Performance tracking |
| MarketData       | Price + indicators   |

---

# ⚠️ Known Limitations

* Only supports OPEN / CLOSE / HOLD
* No scaling or partial exits yet
* Strategy logic partly lives in prompts
* Loop is primary execution engine

---

# 🛠 Setup

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

# 🧠 Philosophy

The system works when these align:

* STATE
* PROMPTS
* PARSER
* EXECUTION

👉 This is not just automation
👉 It is **controlled AI decision-making**

---

# 📌 Final Thought

The strength of this system is:

**Structured state + adaptive AI + strict execution rules**

That is what makes it scalable.

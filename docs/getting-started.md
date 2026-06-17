# Getting Started
**Version: v1**

Welcome to Tracker 👋

This guide will help you go from zero → running your first strategy.

---

# 1. What is Tracker?

Tracker is a system that:

- finds trading opportunities
- simulates trades (backtest)
- helps you improve strategies

👉 In simple terms:

> It helps you test if a trading idea actually works before risking money.

---

# 2. What does the system do?

Tracker:

1. Reads market data
2. Applies your strategy rules
3. Simulates trades
4. Shows results

---

# 3. What you need before starting

You need:

- the project running (Laravel app)
- market data configured (Yahoo / Finnhub)
- basic understanding of:
  - entry
  - stop-loss
  - take-profit

---

# 4. Your first run (step-by-step)

## Step 1 — Check settings

Open:

```
config/strategy.php
```

Important settings:

- `range_minutes`
- `take_profit_rr`
- `enable_trailing_stop`
- `risk_per_trade`

👉 Don’t change too much at once

---

## Step 2 — Run a backtest

Use:

```
php artisan profiles:backtest
```

Or your dashboard (if using UI)

---

## Step 3 — View results

Look at:

- trades
- profit
- win rate
- drawdown

---

# 5. How trades work (simple explanation)

Every trade has:

- entry → where we buy
- stop-loss → where we accept loss
- target → where we expect profit

---

## Important rule

If trailing is OFF:

👉 target = exit

If trailing is ON:

👉 target = trigger → trade continues

---

# 6. What to focus on (IMPORTANT)

Do NOT focus only on profit.

Focus on:

- consistency
- drawdown
- expectancy

---

# 7. Common beginner mistakes

### ❌ Changing too many settings
→ You don’t know what caused results

---

### ❌ Trusting one backtest
→ Could be luck

---

### ❌ Ignoring losses
→ Risk matters more than profit

---

# 8. How to improve your strategy

Do this:

1. Run baseline
2. Change ONE setting
3. Run again
4. Compare

Repeat.

---

# 9. What to do next

After your first run:

- read `strategy-explained.md`
- read `metrics-explained.md`
- test multiple tickers
- refine settings

---

# 10. One-line summary

> Start simple, test carefully, improve step by step

---

# Final note

You don’t need to be perfect.

You just need to:
- test
- learn
- improve

That’s how real trading systems are built.

# Tracker Core Engine Spec
**Version: v1**

This document defines the clean core trading engine for Tracker.

The goal is to remove confusion, reduce overlapping settings, and create one clear foundation for how the engine should work.

This spec is not about the whole app.  
It is about the **trading engine core** only.

That means it answers:

- what the engine is responsible for
- what settings belong inside the engine
- what settings do not belong inside the engine
- how the engine should make decisions
- which duplicated settings should be merged or removed

---

## 1. Purpose of the engine

The core engine exists to do one job:

> decide when a trade is valid, how much risk it takes, and how that trade is managed from entry to exit

The engine should stay small, clear, and predictable.

It should not be full of UI settings, report settings, or integration details unless those are truly needed for trading logic.

---

## 2. What belongs inside the engine core

The engine core should only contain settings and logic for:

- trading session rules
- setup construction
- entry validation
- stop placement
- take-profit logic
- risk sizing
- trade limits
- simulation realism required for backtests

---

## 3. What does NOT belong inside the engine core

These should live outside the true engine core:

- currency display
- chart preferences
- leaderboard settings
- profile page filters
- diagnostics limits
- OAuth and broker login settings
- provider-specific display quirks
- general UI settings

These things are important to the app, but they are not part of the pure trading decision engine.

---

## 4. Core engine responsibilities

The engine should be responsible for these steps:

1. confirm market data is available
2. confirm current time is inside the allowed session
3. build the setup range
4. validate volatility / filter conditions
5. detect breakout condition
6. validate entry buffer
7. validate retest rule if required
8. compute stop loss
9. compute position size
10. reject trade if limits are exceeded
11. compute take profit
12. simulate or execute using realism settings

This defines the engine flow.

---

## 5. Canonical engine settings

These are the settings that should define the clean engine.

## A. Session settings

### `session_start`
**Meaning:**  
When the engine is allowed to begin trading.

**Simple explanation:**  
This is when the game starts.

### `session_end`
**Meaning:**  
When the engine must stop opening new trades.

**Simple explanation:**  
This is when the game stops allowing new moves.

### `range_minutes`
**Meaning:**  
How many minutes are used to build the opening/setup range.

**Simple explanation:**  
This decides how long we watch before drawing the setup box.

---

## B. Entry settings

### `entry_buffer_percent`
**Meaning:**  
How far price must move beyond the level before an entry is valid.

**Simple explanation:**  
This makes the engine wait a little more before trusting the breakout.

### `require_retest`
**Meaning:**  
Whether price must come back and retest the breakout level before entry.

**Simple explanation:**  
This says whether the price must “check the broken door” before we enter.

### `min_atr`
**Meaning:**  
Minimum volatility required before the engine is allowed to trade.

**Simple explanation:**  
If the market is too sleepy, do not trade.

---

## C. Exit settings

### `sl_buffer_percent`
**Meaning:**  
Extra buffer used for stop-loss placement.

**Simple explanation:**  
Gives the stop a little breathing room.

### `take_profit_rr`
**Meaning:**  
Target profit based on risk/reward ratio.

**Simple explanation:**  
This says how much the engine wants to win compared with what it risks.

### `enable_trailing_stop`
**Meaning:**  
Whether the stop should move with price as the trade goes in the right direction.

**Simple explanation:**  
The safety rope follows the trade upward.

---

## D. Risk settings

### `risk_per_trade`
**Meaning:**  
How much account risk is allowed per trade.

**Simple explanation:**  
How much of the treasure chest we are willing to risk on one idea.

### `max_trades_per_ticker`
**Meaning:**  
How many times one symbol can be traded in a day.

**Simple explanation:**  
Do not keep playing the same toy too many times.

### `max_trades_per_day`
**Meaning:**  
Total maximum number of trades per day.

**Simple explanation:**  
Daily excitement limit.

---

## E. Simulation realism settings

### `fee_percent`
**Meaning:**  
Percentage fee charged per trade.

**Simple explanation:**  
Every trade gives a small bite to the broker.

### `fee_min_per_order`
**Meaning:**  
Minimum fee charged per order.

**Simple explanation:**  
Even tiny trades may still have a minimum cost.

### `execution_delay_sec`
**Meaning:**  
Delay before an order is treated as filled.

**Simple explanation:**  
How long the engine waits before saying the trade really happened.

---

## F. Market/data settings

### `datafeed`
**Meaning:**  
Which market data source is used.

**Simple explanation:**  
Which price station the engine listens to.

### `ticker_list`
**Meaning:**  
Which universe of symbols can be traded.

**Simple explanation:**  
The list of toys the engine is allowed to play with.

---

## 6. Settings that should be merged, removed, or moved

## Merge/remove decision 1
### `allow_retest_entries`
**Decision:** remove or merge into `require_retest`

**Why:**  
Both settings appear to describe almost the same idea.
The clean engine should use one canonical rule only.

**Chosen canonical setting:**  
`require_retest`

---

## Merge/remove decision 2
### `tp_levels` vs `take_profit_rr`
**Decision:** choose one main profit model for core engine

**Why:**  
Keeping both creates confusion unless the engine explicitly supports two distinct exit modes.

**Chosen canonical setting for core:**  
`take_profit_rr`

**Reason:**  
It is simpler, easier to test, and easier to explain.

**Future option:**  
`tp_levels` can return later as an advanced multi-target extension.

---

## Merge/remove decision 3
### `position_usd` vs `risk_per_trade`
**Decision:** choose one true sizing method for core engine

**Why:**  
Fixed dollar size and risk-based sizing are different philosophies.
Keeping both without clear priority causes confusion.

**Chosen canonical setting for core:**  
`risk_per_trade`

**Reason:**  
Risk-based sizing is cleaner and more robust for a serious trading engine.

**Future option:**  
`position_usd` can remain as legacy/manual override mode outside the pure core.

---

## Move outside engine core

These settings should stay in the project, but not in the pure engine definition:

### `CURRENCY`
Display/report setting only.

### `yahoo_suffixes`
Provider-specific integration helper.

### `days`
Runtime backtest/profile tool parameter.

### `limit`
Runtime tool parameter.

### `profile`
Runtime profile-selection parameter.

---

## 7. Final canonical settings list

This is the proposed clean engine core.

### Required canonical settings
- `session_start`
- `session_end`
- `range_minutes`
- `entry_buffer_percent`
- `require_retest`
- `min_atr`
- `sl_buffer_percent`
- `take_profit_rr`
- `enable_trailing_stop`
- `risk_per_trade`
- `max_trades_per_ticker`
- `max_trades_per_day`
- `fee_percent`
- `fee_min_per_order`
- `execution_delay_sec`
- `datafeed`
- `ticker_list`

---

## 8. Optional advanced settings

These are allowed, but should not be part of the smallest clean core:

- `stop_loss_atr_multiplier`
- `tp_levels`
- `position_usd`
- `yahoo_suffixes`

These are useful, but they should be treated as extensions, not foundation rules.

---

## 9. Final engine flow

The clean engine should behave in this order:

### Step 1
Load market data from the selected `datafeed`

### Step 2
Confirm the symbol is allowed by `ticker_list`

### Step 3
Confirm current time is between `session_start` and `session_end`

### Step 4
Build the setup range using `range_minutes`

### Step 5
Check volatility using `min_atr`

### Step 6
Detect breakout condition

### Step 7
Apply `entry_buffer_percent`

### Step 8
If `require_retest = true`, wait for retest

### Step 9
Place stop using `sl_buffer_percent`

### Step 10
Calculate size using `risk_per_trade`

### Step 11
Reject if `max_trades_per_ticker` or `max_trades_per_day` has been exceeded

### Step 12
Set take-profit using `take_profit_rr`

### Step 13
If enabled, manage trailing stop with `enable_trailing_stop`

### Step 14
Apply `fee_percent`, `fee_min_per_order`, and `execution_delay_sec` in simulation/execution layer

---

## 10. Why this spec is cleaner

This version is better because it:

- removes overlapping logic
- reduces ambiguity
- makes backtests easier to trust
- makes the engine easier to explain
- makes future code cleanup easier
- makes ChatGPT-assisted system design easier
- gives Tracker one real trading core instead of many half-overlapping controls

---

## 11. Future extension rules

Any future setting should only be added to the engine core if it directly changes:

- entry validity
- exit behavior
- risk sizing
- trade limits
- session permissions
- simulation realism

If a setting only changes:
- display
- reporting
- diagnostics
- profile browsing
- OAuth
- UI behavior

then it should stay outside the engine core.

---

## 12. Summary

Tracker should use a small, clear trading engine core.

The recommended final foundation is:

- one session model
- one entry validation model
- one stop model
- one profit model
- one primary risk model
- one set of trade-limit controls
- one simulation realism layer
- one market universe/data layer

That gives the project the best chance of staying clean, testable, and scalable.

# Tracker Settings Core
**Version: v2**

This document is the human-readable map of the settings used in Tracker.

It is written in simple language on purpose.

The goal is to make it easy to understand:
- what settings exist
- what each setting controls
- why each setting matters
- where each setting comes from
- whether the setting belongs to the true trading core
- what should stay, move, merge, or be cleaned up later

This document should be understandable even if you are not a developer.

---

## Why this document exists

Tracker has settings in more than one place.

Some settings come from:
- Laravel config files
- the database settings table
- controller or service defaults
- runtime page parameters
- future model/workflow settings
- integration or environment config

When settings are spread out, it becomes hard to know:
- which settings are really important
- which ones affect trading logic
- which ones only affect display
- which ones may overlap or duplicate each other

This document is meant to become the single source of truth for settings.

---

## The big idea

Not all settings are the same.

Some settings decide **how the trading engine behaves**.  
These are **core settings**.

Some settings decide **how the app looks or reports information**.  
These are **app settings**.

Some settings decide **how integrations, workflows, or tools behave**.  
These are **system settings**.

To build the best core, we need to separate those clearly.

---

## Current known settings sources

### 1. Strategy config
These are read from `config('strategy...')`.

This is currently the most important source for actual trading behavior.

### 2. Database settings
These are stored through the `settings` table and read using `SettingsService`.

This is useful for flexible app-level or runtime-editable settings.

### 3. Runtime/request parameters
Some tools and profile pages use runtime input like:
- `days`
- `limit`
- `profile`

These are not always permanent settings, but they still affect behavior.

### 4. Environment/integration config
These are likely used for broker credentials, OAuth setup, and external services.

---

# Settings framework

The database-backed settings system supports these fields:

### `key`
The name of the setting.

**Kid explanation:**  
This is the name written on the settings box.

### `group`
The category the setting belongs to.

**Kid explanation:**  
This is the shelf where the box is stored.

### `type`
The type of data in the setting.

Supported types include:
- `string`
- `bool`
- `int`
- `float`
- `time`
- `json`

**Kid explanation:**  
This tells us if the box contains a word, a number, a yes/no switch, a time, or a list.

### `value`
The actual saved value.

**Kid explanation:**  
This is what is inside the box.

### `label`
A friendly human name.

**Kid explanation:**  
This is the nice sticker on the front of the box.

### `meta`
Extra information about the setting.

**Kid explanation:**  
This is the note attached to the box with extra instructions.

---

# Full verified settings map (v2)

Below is the current settings list verified from code.

---

## A. Core strategy structure settings

### 1. `range_minutes`
**What it controls:**  
How many minutes are used to build the opening/setup range.

**Kid explanation:**  
You watch the market for a little while and draw a box around price. This decides how long you watch before drawing the box.

**Why it matters:**  
Small range = more signals, faster behavior.  
Large range = slower behavior, sometimes safer.

**Category:**  
Core trading engine

**Recommendation:**  
Keep in true core.

---

### 2. `entry_buffer_percent`
**What it controls:**  
How far price must move beyond a breakout level before entry is valid.

**Kid explanation:**  
Instead of trusting the first tiny poke above the line, this makes the trade wait a little more.

**Why it matters:**  
Helps filter out fake breakouts.

**Category:**  
Core trading engine

**Recommendation:**  
Keep in true core.

---

### 3. `require_retest`
**What it controls:**  
Whether a breakout must come back and retest the level before entry.

**Kid explanation:**  
The price breaks the door, then comes back to see if the door is really open. This decides if we wait for that.

**Why it matters:**  
Can improve entry quality, but may reduce trade count.

**Category:**  
Core trading engine

**Recommendation:**  
Keep in true core.

---

### 4. `allow_retest_entries`
**What it controls:**  
Whether retest-style entries are allowed.

**Kid explanation:**  
This is another yes/no switch that says if “come back and test the level” entries are allowed.

**Why it matters:**  
This may overlap with `require_retest`.

**Category:**  
Core trading engine

**Recommendation:**  
Review for possible merge with `require_retest`.

---

### 5. `sl_buffer_percent`
**What it controls:**  
Extra percent buffer added to stop-loss placement.

**Kid explanation:**  
It gives the stop a little extra space so normal wiggles do not kick the trade out too early.

**Why it matters:**  
Too small = lots of stop-outs.  
Too large = bigger losses.

**Category:**  
Core trading engine

**Recommendation:**  
Keep in true core.

---

### 6. `tp_levels`
**What it controls:**  
Take-profit levels defined as multiples of risk.

**Kid explanation:**  
These are the places where the trade says, “I have made enough here, maybe I should take some profit.”

**Why it matters:**  
Defines reward structure and exit behavior.

**Category:**  
Core trading engine

**Recommendation:**  
Keep in true core.

---

### 7. `take_profit_rr`
**What it controls:**  
Take-profit target based on risk/reward ratio.

**Kid explanation:**  
This says how many “risk units” the trade wants to win before it exits.

**Why it matters:**  
This may overlap with `tp_levels`.

**Category:**  
Core trading engine

**Recommendation:**  
Review for possible merge with `tp_levels`.

---

### 8. `enable_trailing_stop`
**What it controls:**  
Whether the stop follows price when the trade moves in your favor.

**Kid explanation:**  
It is like a safety rope that climbs up after you so you do not fall all the way back down.

**Why it matters:**  
Can protect gains, but may cut winners too early.

**Category:**  
Core trading engine

**Recommendation:**  
Keep in true core.

---

### 9. `session_start`
**What it controls:**  
The time when the strategy is allowed to begin.

**Kid explanation:**  
This is when the game starts.

**Why it matters:**  
Markets behave differently at different times.

**Category:**  
Core trading engine

**Recommendation:**  
Keep in true core.

---

### 10. `session_end`
**What it controls:**  
The time when the strategy stops opening new trades.

**Kid explanation:**  
This is when the game stops letting you start new moves.

**Why it matters:**  
Prevents trades outside the intended session.

**Category:**  
Core trading engine

**Recommendation:**  
Keep in true core.

---

### 11. `min_atr`
**What it controls:**  
Minimum ATR required to allow trading.

**Kid explanation:**  
If the market is too sleepy and not moving enough, this setting says, “Do not play.”

**Why it matters:**  
Can filter out dead, low-volatility conditions.

**Category:**  
Core trading engine / filter

**Recommendation:**  
Keep in core, under filters.

---

## B. Risk and money management settings

### 12. `position_usd`
**What it controls:**  
How much money is put into a trade.

**Kid explanation:**  
This is how big your money bucket is for each trade.

**Why it matters:**  
Changes account exposure and position size.

**Category:**  
Core risk setting

**Recommendation:**  
Keep in true core.

---

### 13. `risk_per_trade`
**What it controls:**  
How much of the account is risked per trade.

**Kid explanation:**  
This says how much of your treasure chest you are willing to risk on one idea.

**Why it matters:**  
Very important for account survival and consistent sizing.

**Category:**  
Core risk setting

**Recommendation:**  
Keep in true core.

---

### 14. `max_trades_per_ticker`
**What it controls:**  
How many times one symbol can be traded in a day.

**Kid explanation:**  
This stops the system from playing the same toy over and over again all day.

**Why it matters:**  
Reduces overtrading on one name.

**Category:**  
Core risk/control setting

**Recommendation:**  
Keep in core.

---

### 15. `max_trades_per_day`
**What it controls:**  
Maximum number of trades per day.

**Kid explanation:**  
This is the daily trade limit so the system does not get too excited.

**Why it matters:**  
Controls overtrading and daily risk.

**Category:**  
Core risk/control setting

**Recommendation:**  
Keep in core.

---

## C. Execution and simulation settings

### 16. `fee_percent`
**What it controls:**  
Percentage trading fee.

**Kid explanation:**  
Every trade gives a little bite to the broker.

**Why it matters:**  
Strong effect on backtests and realistic results.

**Category:**  
Core simulation setting

**Recommendation:**  
Keep in true core.

---

### 17. `fee_min_per_order`
**What it controls:**  
Minimum broker fee per order.

**Kid explanation:**  
Even if the bite should be tiny, the broker may still say, “I take at least this much.”

**Why it matters:**  
Important for small trades.

**Category:**  
Core simulation setting

**Recommendation:**  
Keep in true core.

---

### 18. `execution_delay_sec`
**What it controls:**  
Execution delay before the order is treated as filled.

**Kid explanation:**  
This is how long the system waits before pretending the trade really happened.

**Why it matters:**  
Makes testing more realistic.

**Category:**  
Execution/simulation setting

**Recommendation:**  
Keep, but under execution realism.

---

### 19. `stop_loss_atr_multiplier`
**What it controls:**  
Stop-loss distance based on ATR.

**Kid explanation:**  
Instead of using a fixed distance, this sizes the stop using how much the market normally wiggles.

**Why it matters:**  
Makes stops adapt to volatility.

**Category:**  
Core risk/exit setting

**Recommendation:**  
Keep in core if ATR-based stop logic is truly part of the engine.

---

## D. Datafeed and market-source settings

### 20. `datafeed`
**What it controls:**  
Which market data source is used.

**Kid explanation:**  
This is the station that tells the app what prices are doing.

**Why it matters:**  
Bad data means bad signals.

**Category:**  
System / market data setting

**Recommendation:**  
Keep, but outside pure strategy logic.

---

### 21. `yahoo_suffixes`
**What it controls:**  
Rules for Yahoo-style ticker suffixes.

**Kid explanation:**  
Some stocks need an extra name tag so the data source knows exactly who they are.

**Why it matters:**  
Needed for correct symbol lookup.

**Category:**  
Integration/datafeed setting

**Recommendation:**  
Keep outside pure strategy core.

---

### 22. `ticker_list`
**What it controls:**  
Which ticker file/list is loaded.

**Kid explanation:**  
This is the shopping list of symbols the system looks at.

**Why it matters:**  
Changes what universe the strategy can trade.

**Category:**  
Market universe setting

**Recommendation:**  
Keep, but separate from trade logic.

---

## E. App/display settings

### 23. `CURRENCY`
**What it controls:**  
Which currency is shown in results.

**Kid explanation:**  
This tells the app if money should look like kroner, dollars, or something else.

**Why it matters:**  
Display only, not strategy logic.

**Category:**  
App/display setting

**Recommendation:**  
Keep outside core.

---

## F. Runtime tool/profile controls

These are currently more like runtime knobs than permanent engine settings, but they still matter.

### 24. `days`
**What it controls:**  
How many days of data a profile tool or backtest run should use.

**Kid explanation:**  
This says how far back in time the tool should look.

**Why it matters:**  
Changes backtest range and diagnostics.

**Category:**  
Runtime/backtest control

**Recommendation:**  
Keep as runtime parameter, not global core.

---

### 25. `limit`
**What it controls:**  
How many results or items to process/display.

**Kid explanation:**  
This is how many things the tool is allowed to handle at once.

**Why it matters:**  
Useful for performance and diagnostics.

**Category:**  
Runtime/tool setting

**Recommendation:**  
Keep outside core.

---

### 26. `profile`
**What it controls:**  
Which strategy profile to run or inspect.

**Kid explanation:**  
This chooses which player or setup the tool should look at.

**Why it matters:**  
Important for profile-based testing and diagnostics.

**Category:**  
Runtime/profile setting

**Recommendation:**  
Keep outside global core.

---

# Settings that may overlap and need cleanup

These pairs should be reviewed carefully:

### `require_retest` vs `allow_retest_entries`
These sound similar and may represent the same idea in two different forms.

**Recommendation:**  
Choose one canonical setting name.

### `tp_levels` vs `take_profit_rr`
These both describe profit-taking logic.

**Recommendation:**  
Decide whether the engine should use:
- multi-target exits (`tp_levels`)
- one simple RR target (`take_profit_rr`)
- or both, but with clearly different meaning

### `position_usd` vs `risk_per_trade`
These are both sizing methods.

**Recommendation:**  
Decide whether the engine should size by:
- fixed position amount
- percent risk
- or support both with one clear precedence rule

---

# Proposed canonical settings groups

## 1. Market/session settings
- `datafeed`
- `ticker_list`
- `yahoo_suffixes`
- `session_start`
- `session_end`
- `range_minutes`

## 2. Entry settings
- `entry_buffer_percent`
- `require_retest`
- `allow_retest_entries`
- `min_atr`

## 3. Exit settings
- `sl_buffer_percent`
- `tp_levels`
- `take_profit_rr`
- `enable_trailing_stop`
- `stop_loss_atr_multiplier`

## 4. Risk settings
- `position_usd`
- `risk_per_trade`
- `max_trades_per_ticker`
- `max_trades_per_day`

## 5. Simulation/execution settings
- `fee_percent`
- `fee_min_per_order`
- `execution_delay_sec`

## 6. App/display settings
- `CURRENCY`

## 7. Runtime tooling settings
- `days`
- `limit`
- `profile`

---

# Best guess for the true “core core”

If the goal is to build the cleanest possible core, I would say the most essential settings are:

- `range_minutes`
- `entry_buffer_percent`
- `require_retest`
- `sl_buffer_percent`
- `tp_levels` or `take_profit_rr`
- `enable_trailing_stop`
- `session_start`
- `session_end`
- `risk_per_trade` or `position_usd`
- `fee_percent`
- `fee_min_per_order`
- `min_atr`

These most directly shape how the engine trades.

---

# Recommended next cleanup plan

## Keep in the true engine core
- `range_minutes`
- `entry_buffer_percent`
- `require_retest`
- `sl_buffer_percent`
- `tp_levels` or `take_profit_rr`
- `enable_trailing_stop`
- `session_start`
- `session_end`
- `risk_per_trade` or `position_usd`
- `fee_percent`
- `fee_min_per_order`
- `min_atr`

## Keep, but outside pure engine core
- `datafeed`
- `ticker_list`
- `yahoo_suffixes`
- `execution_delay_sec`

## Keep as runtime/tooling controls
- `days`
- `limit`
- `profile`

## Keep as app/display only
- `CURRENCY`

## Review for merge or simplification
- `require_retest`
- `allow_retest_entries`
- `tp_levels`
- `take_profit_rr`
- `position_usd`
- `risk_per_trade`

---

# What I found in code

The strategy config file defines a broader set of settings than the status page currently shows, including `max_trades_per_ticker`, `min_atr`, `ticker_list`, `execution_delay_sec`, `risk_per_trade`, `stop_loss_atr_multiplier`, `take_profit_rr`, `max_trades_per_day`, and `allow_retest_entries`.

The settings database framework supports grouped, typed settings with `key`, `group`, `type`, `value`, `label`, and `meta`, and typed casting for `bool`, `int`, `float`, `time`, and `json`.

The status page currently shows only a subset of strategy settings: range, entry buffer, retest, stop-loss buffer, TP levels, trailing stop, session times, position size, fee fields, datafeed, and Yahoo suffixes.

Profile/backtest tooling also uses runtime controls like `days`, `limit`, and `profile`, which matter for diagnostics and runs even though they are not part of the global trading core.

---

# Summary

Tracker already has more settings than the first doc captured.

The biggest discoveries in this deeper pass are:
- there are more strategy settings than the status page reveals
- there are overlapping settings that need cleanup
- there are runtime backtest/profile controls that should be documented separately from global core settings
- the app already has the foundations for a much cleaner canonical settings system

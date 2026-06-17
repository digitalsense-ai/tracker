# Tracker Settings Core

This document is the simple, human-readable map of the settings used in Tracker.

The goal is to make it easy to understand:

- what settings exist
- what each setting controls
- where the setting comes from
- whether it belongs to the true trading core
- what we should keep, merge, rename, or move later

This document is written in plain language on purpose.
It should be understandable even if you are not a developer.

---

## Why this document exists

Tracker has settings in more than one place.
Some come from Laravel config files.
Some come from the database settings table.
Some may later come from model-specific pages or integrations.

When settings are spread out, it becomes hard to know:

- which settings really matter
- which settings are duplicates
- which settings define the trading engine core
- which settings are only for display or convenience

This document is the first step toward building one clean settings core.

---

## The most important idea

Not all settings are equally important.

Some settings change how the trading engine behaves.
These are **core settings**.

Some settings only change how the app looks or reports things.
These are **app settings**.

Some settings are about integrations, broker connection, or workflow.
These are **system settings**.

To build the best foundation, we should know exactly which settings belong in each group.

---

## Where settings currently come from

At the moment, Tracker appears to use settings from at least these sources:

### 1. Strategy config
These are settings read through `config('strategy...')`.

These are very important because they appear to define how the strategy behaves.
Examples include range size, entry buffer, stop-loss buffer, and trading session times.

### 2. Database settings
These are stored through the `settings` table and accessed using `SettingsService`.

These settings are flexible and can be changed without editing code.
The database settings system supports:

- `key`
- `group`
- `type`
- `value`
- `label`
- `meta`

Supported value types include:

- `string`
- `bool`
- `int`
- `float`
- `time`
- `json`

### 3. Controller or service defaults
Some values may still be hard-coded inside controllers or services.
This usually means the setting exists in practice, but has not yet been centralized.

### 4. Environment and integration config
Some system-level settings may also come from `.env` files or service config.
These are usually used for broker APIs, app environment, credentials, and external connections.

---

# Verified settings

Below is the current list of settings I can verify from the code that has been inspected so far.

---

## A. Trading strategy core settings

These are the most important settings for the actual trading logic.
They are currently visible through the strategy config layer.

### 1. `range_minutes`
**What it controls:**
How long the opening or setup range should be measured.

**Kid explanation:**
Imagine you watch the market for the first few minutes and draw a box around the high and low. This setting decides how many minutes you use to draw that box.

**Why it matters:**
A small range can create more signals.
A bigger range can make the setup slower and sometimes safer.

**Category:**
Core trading engine

**Recommendation:**
Keep as a core setting.

---

### 2. `entry_buffer_percent`
**What it controls:**
How far price must move beyond the level before it counts as a valid entry.

**Kid explanation:**
Instead of jumping in the second price touches a line, this setting says: “Wait a tiny bit more, just to be sure it really broke out.”

**Why it matters:**
Helps avoid fake breakouts and noise.

**Category:**
Core trading engine

**Recommendation:**
Keep as a core setting.

---

### 3. `require_retest`
**What it controls:**
Whether the strategy must wait for price to come back and test the breakout level before entering.

**Kid explanation:**
Price breaks the door, runs away, then comes back to check if the door is really open. This setting says whether we wait for that check.

**Why it matters:**
Usually reduces bad entries, but may also reduce the number of trades.

**Category:**
Core trading engine

**Recommendation:**
Keep as a core setting.

---

### 4. `sl_buffer_percent`
**What it controls:**
How much extra space is added to the stop loss.

**Kid explanation:**
Instead of putting the stop exactly on the line, this gives it a little breathing room so normal wiggles do not knock the trade out too early.

**Why it matters:**
A stop that is too tight gets hit too often.
A stop that is too wide can make losses bigger.

**Category:**
Core trading engine

**Recommendation:**
Keep as a core setting.

---

### 5. `tp_levels`
**What it controls:**
The take-profit target levels.

**Kid explanation:**
These are the places where the trade says: “I have made enough money here, I may want to take profit.”

**Why it matters:**
Defines how the strategy exits winners and how reward is structured.

**Category:**
Core trading engine

**Recommendation:**
Keep as a core setting.

---

### 6. `enable_trailing_stop`
**What it controls:**
Whether the stop loss should move with price when the trade goes in the right direction.

**Kid explanation:**
It is like a safety rope that follows you upward, so if you fall later, you do not fall all the way back down.

**Why it matters:**
Can protect gains, but can also stop trades too early if used badly.

**Category:**
Core trading engine

**Recommendation:**
Keep as a core setting.

---

### 7. `session_start`
**What it controls:**
The time when the strategy begins looking for setups.

**Kid explanation:**
This is the time when the game starts.

**Why it matters:**
Markets behave differently at different times. The start time decides when the strategy is allowed to begin working.

**Category:**
Core trading engine

**Recommendation:**
Keep as a core setting.

---

### 8. `session_end`
**What it controls:**
The time when the strategy stops opening new setups.

**Kid explanation:**
This is the time when the game stops letting you start new moves.

**Why it matters:**
Stops the system from trading outside the intended session.

**Category:**
Core trading engine

**Recommendation:**
Keep as a core setting.

---

### 9. `position_usd`
**What it controls:**
The cash amount used for each position.

**Kid explanation:**
This is how big your toy bucket is for each trade.
A bigger bucket means bigger wins and bigger losses.

**Why it matters:**
Affects position size, risk, and account exposure.

**Category:**
Core risk setting

**Recommendation:**
Keep as a core setting.

---

### 10. `fee_percent`
**What it controls:**
The percentage fee applied to trades.

**Kid explanation:**
Every time you trade, the broker takes a small bite.
This setting says how big that bite is.

**Why it matters:**
Very important in backtesting and simulation, because fees can turn a good strategy into a bad one.

**Category:**
Core simulation/risk setting

**Recommendation:**
Keep as a core setting.

---

### 11. `fee_min_per_order`
**What it controls:**
The minimum fee charged per order.

**Kid explanation:**
Even if the percentage fee is tiny, the broker may still say: “I take at least this much.”

**Why it matters:**
Especially important when position sizes are small.

**Category:**
Core simulation/risk setting

**Recommendation:**
Keep as a core setting.

---

### 12. `datafeed`
**What it controls:**
Which market data source the strategy uses.

**Kid explanation:**
This is where the app gets its price information from.
It is like choosing which weather station tells you if it is raining.

**Why it matters:**
Bad or delayed data can break everything else.

**Category:**
Core system setting

**Recommendation:**
Keep as a core setting, but separate from pure strategy logic.

---

### 13. `yahoo_suffixes`
**What it controls:**
Ticker symbol suffix rules for Yahoo-style market data.

**Kid explanation:**
Some stocks need an extra name tag at the end so the data provider knows which market they belong to.
This setting helps the app add the right name tag.

**Why it matters:**
Needed for correct symbol lookup when using Yahoo-based data.

**Category:**
Datafeed integration setting

**Recommendation:**
Keep, but classify as integration/datafeed rather than true strategy core.

---

## B. Verified app/database setting

### 14. `CURRENCY`
**What it controls:**
The currency symbol or code used when results are shown.

**Kid explanation:**
This tells the app whether to show money as kroner, dollars, or something else.

**Why it matters:**
Important for display, but does not change the strategy logic itself.

**Category:**
App/display setting

**Recommendation:**
Keep, but outside the trading core.

---

# Settings framework fields

These are not trading settings by themselves.
They are the fields used by the settings system.
Still, they matter because they define how settings are stored.

### `key`
The name of the setting.

**Kid explanation:**
This is the label on the settings box.

### `group`
The folder or category the setting belongs to.

**Kid explanation:**
This is which shelf the settings box sits on.

### `type`
The kind of value the setting stores.

**Kid explanation:**
This tells us if the setting is a word, a number, a yes/no switch, a time, or a bigger list.

### `value`
The actual saved value.

**Kid explanation:**
This is what is inside the settings box.

### `label`
A human-friendly name for the setting.

**Kid explanation:**
This is the pretty sticker on the front of the box.

### `meta`
Extra information about the setting.

**Kid explanation:**
This is the note attached to the box that explains extra rules.

---

# Proposed canonical settings core

To make Tracker easier to manage, I recommend that all settings eventually be grouped into these categories.

## 1. Market and session settings
These decide when and where the strategy is allowed to work.

Should include things like:
- datafeed
- session start
- session end
- range minutes
- timezone
- symbol suffix rules

## 2. Entry settings
These decide what must happen before a trade is allowed.

Should include things like:
- entry buffer
- require retest
- confirmation rules
- breakout conditions

## 3. Exit settings
These decide how trades end.

Should include things like:
- stop-loss buffer
- take-profit levels
- trailing stop on/off
- partial exit rules

## 4. Risk settings
These decide how much money is at risk.

Should include things like:
- position size
- max trades per day
- max exposure
- max loss per trade
- fee model

## 5. Simulation settings
These decide how realistic the backtest is.

Should include things like:
- fee percent
- minimum order fee
- slippage
- fill assumptions
- session rules for historical testing

## 6. Model and workflow settings
These decide how model pages and review flows behave.

Should include things like:
- model defaults
- kanban defaults
- review status rules
- feedback controls
- prompt/logging behavior

## 7. App and display settings
These affect how the app looks or reports data, not how the strategy trades.

Should include things like:
- currency
- chart preferences
- default filters
- export formatting

## 8. Integration settings
These are about external systems.

Should include things like:
- broker connection status
- broker environment
- OAuth setup
- API mode
- provider-specific symbol rules

---

# Recommended keep / move / clean up plan

## Keep in the true trading core
These settings directly shape strategy behavior and should stay central:

- `range_minutes`
- `entry_buffer_percent`
- `require_retest`
- `sl_buffer_percent`
- `tp_levels`
- `enable_trailing_stop`
- `session_start`
- `session_end`
- `position_usd`
- `fee_percent`
- `fee_min_per_order`

## Keep, but classify outside pure strategy core
These are important, but they belong more to system or integration layers:

- `datafeed`
- `yahoo_suffixes`

## Keep as app-level settings
These should not be mixed into the trading core:

- `CURRENCY`

---

# Open questions we should answer next

To make this document complete, we should next inspect and list:

- all config files under `config/`
- settings page fields and validation rules
- model-related settings and defaults
- backtest-specific parameters
- signal engine parameters
- service-level hard-coded defaults
- any broker or Saxo-related config
- any environment variables used for strategy behavior

---

# Suggested next step

The next version of this document should become a full settings registry with a table like this for every option:

- setting name
- plain-English meaning
- kid explanation
- source location
- default value
- used by
- category
- core or non-core
- keep / merge / rename / remove

That would give Tracker one clear and trustworthy settings map.

---

# Summary

Tracker already has a meaningful settings foundation, but the settings are spread across different layers.

Right now, the most important verified core settings are:

- range size
- entry buffer
- retest requirement
- stop-loss buffer
- take-profit levels
- trailing stop
- session start/end
- position size
- trading fees
- datafeed selection

These are the settings that most directly control how the engine behaves.

The main goal going forward should be:

**one clean canonical settings core, with plain explanations, clear ownership, and no duplicates**.

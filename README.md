# 📊 ORB Trading Tracker

A Laravel-based web application for real-time stock strategy analysis using Opening Range Breakout (ORB), gap forecasting, and simulated trade tracking.

---

## 🔍 Features Overview

- ✅ Live forecast screening based on gap %, volume, and RVOL
- 📈 Strategy phase tracking: Forecast → Breakout → Retest → Entry → Exit
- 🤖 Automated simulation of trades (entry/exit logic)
- 💰 Result table with net profit, fees, and Nordnet execution flag
- 🕒 Daily data refresh via scheduled cron jobs

---

## 🧱 Requirements

- PHP 8.1+
- Laravel 10+
- MySQL or MariaDB
- Composer
- Node.js and npm (for frontend assets)

---

## 🗂 Project Structure

| Folder | Purpose |
|--------|---------|
| `resources/views/` | Blade templates (`dashboard`, `explainer`, `results`) |
| `app/Http/Controllers/` | Route logic and rendering |
| `app/Console/Commands/` | Custom Artisan commands (forecast + simulation) |
| `database/migrations/` | DB schema |
| `public/` | Static assets, favicon, CSS |

---

## 🛠 Simulated Trades Table Fields

| Field | Description |
|-------|-------------|
| `ticker` | Stock ticker symbol |
| `entry_price` | Simulated entry price |
| `exit_price` | Simulated exit price |
| `fees` | Calculated Nordnet fee |
| `net_profit` | Profit or loss |
| `earnings_day` | True if on earnings release day |
| `forecast_type` | Gap-up, gap-down, consolidation, etc. |
| `forecast_score` | Score or quality rank |
| `trend_rating` | Simple trend assessment |
| `executed_on_nordnet` | True if it matched Nordnet trading availability |

---

## ⚙️ Forecast Type Options

- `gap-up`
- `gap-down`
- `consolidation`
- `volatility-squeeze`
- `breakout-ready`
- `mean-revert`

---

## ⏱ Cron Setup

To automate daily scans and simulations, add this to your crontab:

```bash
* * * * * cd /var/www/tracker && php artisan schedule:run >> /dev/null 2>&1
```

Or run manually:

```bash
php artisan forecast:scan
php artisan simulate:trades
```

---

## 📊 URLs

- `/dashboard` → Full ORB trading panel
- `/explainer` → Definition and glossary of terms and filters
- `/results` → Simulated trade history and performance

---

## ✅ Status

- [x] Forecast and Strategy Dashboard
- [x] Forecast Settings UI
- [x] Retest and Entry Simulation
- [x] Results with Profit & Fees
- [x] Cron Integration
- [ ] Real Nordnet integration (planned)
- [ ] Live chart embedding (planned)


---

## 🤖 AI Trading Models (Pre-Market + Loop Design)

The project includes an experimental **AI model engine** for autonomous trading decisions.

Each AI model can be configured under <code>/models</code> with:

- <b>Pre-Market Prompt</b> – a large "planning" prompt that runs before the market opens and builds a daily strategy playbook.
- <b>Start / Loop Prompts</b> – lighter prompts that control how the model manages trades during the day.
- <b>Pre-Market Run Time</b> – <code>HH:MM</code> time for when the pre-market planner should run (via Laravel scheduler).
- <b>Max Strategies / Symbols per Day</b> – soft caps to limit how many ideas the AI can propose.
- <b>Sleeper Strategies</b> – optional strategies that only activate later in the day when price enters a defined zone.
- <b>Risk Settings</b> – default risk per strategy, max adds per position, and whether the loop is allowed to:
  - activate sleeper strategies
  - exit early when an invalidation condition is met
- <b>Loop Min Price Move (%)</b> – optional threshold to decide when it is worth calling the AI loop (to save tokens).

> Implementation note: the current <code>ai:tick</code> command uses the loop prompt directly. The new pre-market fields are designed to support a 3-phase flow:
> 1) Pre-market planning, 2) mechanical execution at open, 3) intraday loop/risk management.

You can extend <code>app/Console/Commands</code> with a dedicated <code>AiPremarket</code> command that reads these settings from <code>ai_models</code> and calls your chosen LLM for plan generation.


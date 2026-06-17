# Strategy Explained
**Version: v2**

This document explains the trading strategy used in Tracker in simple language.

## Big picture
A breakout + retest strategy based on a defined time range.

## Core steps
1. Build range (`range_minutes`)
2. Wait for breakout
3. Apply buffer (`entry_buffer_percent`)
4. Optional retest (`require_retest`)
5. Check volatility (`min_atr`)
6. Enter trade
7. Place stop (`sl_buffer_percent`)
8. Size via risk (`risk_per_trade`)
9. Set TP (`take_profit_rr`)
10. Optional trailing stop

## Why it works
- Breakouts = momentum
- Retests = confirmation
- Risk control = survival

## Summary
Wait → confirm → enter → manage risk → exit.
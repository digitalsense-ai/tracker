#!/usr/bin/env bash
set -euo pipefail
export SHELL=/bin/bash
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/bin
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"
LOG="storage/logs/nyopen.log"
PHP_BIN="/usr/bin/php"
echo "[$(date -Is)] START nyopen:backtest" >> "$LOG"
$PHP_BIN artisan nyopen:backtest --date=yesterday -vvv >> "$LOG" 2>&1 || true
echo "[$(date -Is)] DONE nyopen:backtest exit=$?" >> "$LOG"

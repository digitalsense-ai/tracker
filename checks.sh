#!/usr/bin/env bash
set -euo pipefail
echo "=== ENV & PATH ==="
which php || true
php -v || true
echo "APP DIR: $(pwd)"
echo "ENV:"; php -r 'echo getenv("APP_ENV");' || true; echo
echo "QUEUE_CONNECTION:"; php -r 'echo getenv("QUEUE_CONNECTION");' || true; echo
echo "=== Artisan ==="; php artisan --version || true
echo "=== Crontab ==="; crontab -l || echo "(no crontab)"
echo "=== Supervisor ==="
if command -v supervisorctl >/dev/null 2>&1; then supervisorctl status || true; else echo "(supervisorctl not found)"; fi
echo "=== Try run nyopen:backtest (yesterday) ==="; php artisan nyopen:backtest --date=yesterday -vvv || true
echo "=== Logs ==="; ls -l storage/logs || true; tail -n 200 storage/logs/laravel.log || true; tail -n 200 storage/logs/nyopen.log || true
echo "=== DB sanity (run SQL manually) ==="; echo "SOURCE sql/quick_sanity.sql"

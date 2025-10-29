#!/usr/bin/env bash
set -euo pipefail

php artisan optimize:clear
composer dump-autoload -o

# Recompute last 30 days from simulated_trades using created_at
php artisan profiles:recompute --days=30 -v --table=simulated_trades --ts=created_at

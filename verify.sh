#!/usr/bin/env bash
set -euo pipefail
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"
echo "=== Running quick wrapper once ==="
./run_nyopen_quick.sh || true
echo "=== Last 200 lines of nyopen.log ==="
tail -n 200 storage/logs/nyopen.log || true

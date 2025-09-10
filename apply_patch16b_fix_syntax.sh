#!/bin/bash
set -e

echo "== PATCH16B Syntax Fix =="

CANDIDATES=("$PWD" "$(cd "$(dirname "$0")" && pwd)" "$(cd "$(dirname "$0")" && pwd)/.." "/var/www/tracker")
PROJECT_DIR=""
for c in "${CANDIDATES[@]}"; do
  if [ -f "$c/artisan" ]; then PROJECT_DIR="$c"; break; fi
done
if [ -z "$PROJECT_DIR" ]; then
  echo "❌ Could not find Laravel project root (artisan not found)."
  exit 1
fi
echo "Project: $PROJECT_DIR"

WEB="$PROJECT_DIR/routes/web.php"
BK="$WEB.bak_patch16b_fix_$(date +%s)"
cp "$WEB" "$BK"
echo "Backup: $BK"

if ! head -n1 "$WEB" | grep -q "^<?php"; then
  echo ">> Prepending <?php to routes/web.php"
  (echo "<?php"; cat "$WEB") > "$WEB.tmp" && mv "$WEB.tmp" "$WEB"
fi

if grep -q "?>" "$WEB"; then
  echo ">> Removing closing PHP tags '?>' inside routes/web.php"
  sed -E -i "s/\?>//g" "$WEB"
fi

if ! php -l "$WEB" >/dev/null; then
  echo "❌ Still failing PHP syntax. Showing context around PATCH16B:"
  nl -ba "$WEB" | sed -n '/PATCH16B/,+60p'
  exit 1
fi

(cd "$PROJECT_DIR" && php artisan optimize:clear || true)
(cd "$PROJECT_DIR" && php artisan route:list | grep -E "_profiles_tools|profiles\.tools" || true)

echo "✅ Syntax fixed. Try hitting /_profiles_tools"

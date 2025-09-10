#!/bin/bash
set -e

echo "== Applying PATCH16 Profiles Tools (idempotent) =="

# Determine script dir and project dir (where artisan lives)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CANDIDATES=( "$PWD" "$SCRIPT_DIR" "$SCRIPT_DIR/.." "/var/www/tracker" )

PROJECT_DIR=""
for c in "${CANDIDATES[@]}"; do
  if [ -f "$c/artisan" ]; then PROJECT_DIR="$c"; break; fi
done
if [ -z "$PROJECT_DIR" ]; then
  echo "❌ Could not locate Laravel project root (artisan not found)."
  exit 1
fi
echo "Project: $PROJECT_DIR"

# Source dir (where route/view files live)
if [ -f "$SCRIPT_DIR/routes/web.profiles_tools.php" ]; then
  SRC="$SCRIPT_DIR"
else
  SRC="$PWD"
fi

# Ensure target dirs
mkdir -p "$PROJECT_DIR/resources/views/profiles" "$PROJECT_DIR/routes"

copy_if_needed () {
  local src="$1"; local dst="$2"
  if [ "$(realpath "$src")" = "$(realpath "$dst")" ]; then
    echo ">> Skipping copy (same file): $dst"
  else
    cp -v "$src" "$dst"
  fi
}

copy_if_needed "$SRC/routes/web.profiles_tools.php" "$PROJECT_DIR/routes/web.profiles_tools.php"
copy_if_needed "$SRC/resources/views/profiles/run.blade.php" "$PROJECT_DIR/resources/views/profiles/run.blade.php"

# Add require line if missing
if ! grep -q "web.profiles_tools.php" "$PROJECT_DIR/routes/web.php"; then
  echo "require base_path('routes/web.profiles_tools.php');" >> "$PROJECT_DIR/routes/web.php"
  echo ">> Added require to routes/web.php"
else
  echo ">> Require already present in routes/web.php"
fi

# Clear caches
(cd "$PROJECT_DIR" && php artisan optimize:clear || true)
(cd "$PROJECT_DIR" && php artisan route:list | grep -E "profiles\.tools" || true)

echo "✅ PATCH16 applied. Open /profiles/tools"

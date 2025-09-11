#!/bin/bash
set -e

echo "== PATCH16B Wrap Block Fix =="

# Find project root
CANDIDATES=("$PWD" "$(cd "$(dirname "$0")" && pwd)" "$(cd "$(dirname "$0")" && pwd)/.." "/var/www/tracker")
PROJECT_DIR=""
for c in "${CANDIDATES[@]}"; do
  if [ -f "$c/artisan" ]; then PROJECT_DIR="$c"; break; fi
done
[ -z "$PROJECT_DIR" ] && echo "artisan not found" && exit 1
echo "Project: $PROJECT_DIR"

WEB="$PROJECT_DIR/routes/web.php"
BK="$WEB.bak_patch16b_wrap_$(date +%s)"
cp "$WEB" "$BK"
echo "Backup: $BK"

# 1) Ensure file starts with <?php and remove stray closing tags
if ! head -n1 "$WEB" | grep -q "^<?php"; then
  (echo "<?php"; cat "$WEB") > "$WEB.tmp" && mv "$WEB.tmp" "$WEB"
fi
sed -E -i "s/\?>//g" "$WEB"

# 2) If PATCH16B block is outside PHP, inject an opening tag right before it
# We consider the FIRST line that contains 'PATCH16B' and ensure previous non-empty line has '<?php'
awk '
  BEGIN{need_inject=0}
  {
    lines[NR]=$0
    if(index($0,"PATCH16B")>0 && first==0){
      first=NR
    }
  }
  END{
    for(i=1;i<=NR;i++){
      print lines[i]
      if(i==first-1){
        # look back a few lines to see if we are likely outside PHP (no "<?php" in last 5 lines)
        found=0
        for(j=i;j>=i-5 && j>=1;j--){
          if(index(lines[j],"<?php")>0){found=1; break}
        }
        if(found==0){
          print "<?php"
        }
      }
    }
  }
' "$WEB" > "$WEB.tmp" && mv "$WEB.tmp" "$WEB"

# 3) Lint and show context
if ! php -l "$WEB" >/dev/null; then
  echo "❌ Still failing. Showing lines around PATCH16B:"
  nl -ba "$WEB" | sed -n '/PATCH16B/,+60p'
  exit 1
fi

(cd "$PROJECT_DIR" && php artisan optimize:clear || true)
(cd "$PROJECT_DIR" && php artisan route:list | grep -E "_profiles_tools|profiles\\.tools" || true)

echo "✅ Wrapped PATCH16B block. Try /_profiles_tools"

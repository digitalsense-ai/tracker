#!/bin/bash
set -e
CANDIDATES=("$PWD" "$(cd "$(dirname "$0")" && pwd)" "$(cd "$(dirname "$0")" && pwd)/.." "/var/www/tracker")
PROJECT_DIR=""
for c in "${CANDIDATES[@]}"; do if [ -f "$c/artisan" ]; then PROJECT_DIR="$c"; break; fi; done
[ -z "$PROJECT_DIR" ] && echo "artisan not found" && exit 1
echo "Project: $PROJECT_DIR"

CSS_DST="$PROJECT_DIR/public/css/tracker-light.css"
PARTIAL_DST_DIR="$PROJECT_DIR/resources/views/layouts/partials"
LAYOUT="$PROJECT_DIR/resources/views/layouts/app.blade.php"
mkdir -p "$(dirname "$CSS_DST")" "$PARTIAL_DST_DIR"

# Write files
cat > "$CSS_DST" <<'CSS'
:root{
  --bg:#f6f8fb; --card:#ffffff; --text:#0f172a; --muted:#64748b;
  --green:#15803d; --red:#b91c1c; --amber:#b45309; --blue:#1d4ed8;
  --border:#e2e8f0; --shadow:0 8px 28px rgba(2,6,23,.06);
}
body.tracker{ background:var(--bg); color:var(--text);
  -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale; }
.card,.panel,.box,.tile,.shadow,.rounded{ background:var(--card)!important; color:var(--text)!important;
  box-shadow:var(--shadow); border:1px solid var(--border); border-radius:12px; }
table{ width:100%; border-collapse:collapse; background:var(--card); }
thead th{ text-align:left; font-weight:600; color:var(--muted); border-bottom:1px solid var(--border); }
tbody td{ border-bottom:1px solid var(--border); padding:.6rem .8rem; }
tbody tr:hover{ background:#fafbff; }
.table{ background:var(--card); }
.table thead th,.table td{ border-color:var(--border); }
.badge,.tag{ display:inline-block; border-radius:999px; padding:.25rem .6rem; font-size:.85rem;
  border:1px solid var(--border); background:#fff; color:var(--text); }
.badge.green{ background:#ecfdf5; color:var(--green); border-color:#a7f3d0; }
.badge.red{ background:#fef2f2; color:var(--red); border-color:#fecaca; }
.badge.blue{ background:#eff6ff; color:var(--blue); border-color:#bfdbfe; }
.badge.amber{ background:#fffbeb; color:var(--amber); border-color:#fde68a; }
a{ color:var(--blue); }
.btn,.button{ display:inline-flex; align-items:center; justify-content:center; padding:.55rem .9rem;
  border-radius:10px; border:1px solid var(--border); background:#fff; color:var(--text); cursor:pointer; }
.btn-primary{ background:var(--blue); color:#fff; border-color:transparent; }
.btn-ghost{ background:#fff; color:var(--text); }
.btn:hover{ filter:brightness(0.98); } .btn-primary:hover{ filter:brightness(0.95); }
input,select,textarea{ background:#fff; color:var(--text); border:1px solid var(--border);
  border-radius:10px; padding:.55rem .7rem; }
input::placeholder,textarea::placeholder{ color:#94a3b8; }
h1,h2,h3{ color:var(--text); margin:0 0 .6rem; }
small,.muted,.text-muted{ color:var(--muted)!important; }
.border{ border:1px solid var(--border); }
.rounded{ border-radius:12px; } .shadow{ box-shadow:var(--shadow); }
.bg-card{ background:var(--card); }
CSS

cat > "$PARTIAL_DST_DIR/theme.blade.php" <<'BLADE'
<link rel="stylesheet" href="{{ asset('css/tracker-light.css') }}">
BLADE


# Inject partial include before </head>
if [ -f "$LAYOUT" ]; then
  if ! grep -q "layouts.partials.theme" "$LAYOUT"; then
    awk '/<\/head>/ && !x { print "    @include(\x27layouts.partials.theme\x27)"; x=1 } { print }' "$LAYOUT" > "$LAYOUT.tmp" && mv "$LAYOUT.tmp" "$LAYOUT"
  fi
  if grep -q "<body[^>]*class=" "$LAYOUT"; then
    if ! grep -q '<body[^>]*class="[^"]*tracker' "$LAYOUT"; then
      sed -E -i 's/(<body[^>]*class=")([^"]*)"/\1\2 tracker"/' "$LAYOUT"
    fi
  else
    sed -E -i 's/<body(>|\s*>)/<body class="tracker"\1/' "$LAYOUT"
  fi
else
  echo "⚠ $LAYOUT not found. Add @include('layouts.partials.theme') and body.tracker manually."
fi

(cd "$PROJECT_DIR" && php artisan view:clear || true && php artisan optimize:clear || true)
echo "✅ Light theme applied."

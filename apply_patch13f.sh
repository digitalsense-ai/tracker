#!/bin/bash
set -e
mkdir -p app/Support
if [ -f app/Services/BacktestShim.php ]; then
  mv app/Services/BacktestShim.php app/Support/BacktestShim.php
fi
cat > app/Support/BacktestShim.php <<'PHP'
<?php
namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Carbon\Carbon;

class BacktestShim
{
    public static function run(Carbon $start, int $days, array $settings = [], bool $verbose = false): array
    {
        if (App::bound('App\Services\BacktestService')) {
            $svc = App::make('App\Services\BacktestService');
            foreach (['simulateForDate','simulate','simulateRange'] as $m) {
                if (method_exists($svc, $m)) {
                    try {
                        if ($m === 'simulateForDate') {
                            return self::normalize($svc->$m($start, $days, ['profile_settings'=>$settings]));
                        } elseif ($m === 'simulate') {
                            return self::normalize($svc->$m($days, ['profile_settings'=>$settings,'start'=>$start]));
                        } else {
                            $end = (clone $start)->addDays($days);
                            return self::normalize($svc->$m($start, $end, ['profile_settings'=>$settings]));
                        }
                    } catch (\Throwable $e) {
                        if ($verbose) logger()->warning('BacktestShim '.$m.' failed: '.$e->getMessage());
                    }
                }
            }
        }

        try { Artisan::call('backtest:simulate-v5', ['--days' => $days]); }
        catch(\Throwable $e){ if ($verbose) logger()->warning('BacktestShim artisan call failed: '.$e->getMessage()); }

        $end = (clone $start)->addDays($days);
        $tbl = 'simulated_trades';
        if (!DB::getSchemaBuilder()->hasTable($tbl)) return [];

        $cols = DB::getSchemaBuilder()->getColumnListing($tbl);
        $dateCol = in_array('date',$cols) ? 'date' : (in_array('executed_at',$cols)?'executed_at':(in_array('created_at',$cols)?'created_at':null));

        $q = DB::table($tbl);
        if ($dateCol) {
            if ($dateCol === 'date') {
                $q->whereBetween($dateCol, [$start->toDateString(), $end->toDateString()]);
            } else {
                $q->whereBetween($dateCol, [$start->toDateTimeString(), $end->toDateTimeString()]);
            }
        }
        $rows = $q->orderBy($dateCol ?? DB::raw('1'))->limit(5000)->get();

        $trades = [];
        foreach ($rows as $r) {
            $trades[] = [
                'date' => (string)($r->$dateCol ?? ''),
                'ticker' => (string)($r->ticker ?? ($r->symbol ?? '-')),
                'status' => (string)($r->status ?? 'closed'),
                'entry' => self::f($r,'entry_price','entry'),
                'sl' => self::f($r,'sl_price','stop','stop_loss'),
                'exit' => self::f($r,'exit_price','exit','close_price'),
                'qty' => self::f($r,'qty','quantity','size',1),
                'fees_bps' => self::f($r,'fees_bps','fee_bps',0),
                'fees' => self::f($r,'fees',0),
                'side' => (string)($r->side ?? $r->direction ?? $r->type ?? 'long'),
                'net' => self::f($r,'net_profit','net','pnl',0),
                'R' => self::f($r,'R','r',null)
            ];
        }
        return $trades;
    }

    protected static function f($obj, ...$keys)
    {
        $default = null;
        if (count($keys) && is_numeric(end($keys))) { $default = array_pop($keys); }
        foreach ($keys as $k) { if ($k && isset($obj->$k)) return (float)$obj->$k; }
        return $default;
    }

    protected static function normalize($res): array
    {
        if (is_array($res)) {
            if (isset($res['trades']) && is_array($res['trades'])) return $res['trades'];
            return $res;
        }
        return [];
    }
}

PHP

append_if_missing () {
  local file="routes/web.php"
  local require_line="$1"
  grep -qF "$require_line" "$file" || echo "$require_line" >> "$file"
}
append_if_missing "require base_path('routes/web.signals_pretty.php');"
append_if_missing "require base_path('routes/web.profiles_run.php');"
append_if_missing "require base_path('routes/web.profiles_diag.php');"

composer dump-autoload -q || true
php artisan route:clear || true

echo "✅ Applied PSR-4 + routes fixes."

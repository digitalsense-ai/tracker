<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Stock;
use Carbon\Carbon;

class StrategyEngine
{
    /**
     * Run ORB+Retest simulation for a single ticker and date with a parameter set.
     * Returns an associative array with trade results (or null if no trade).
     *
     * $params array keys (with defaults):
     *  - range_minutes (int) default 15
     *  - entry_buffer_percent (float) default 0.0
     *  - require_retest (bool) default true
     *  - sl_buffer_percent (float) default 0.0
     *  - tp_levels (array of floats) default [1.0, 2.0]
     *  - enable_trailing_stop (bool) default false
     *  - session_start (string 'HH:MM') default '09:30'
     *  - session_end   (string 'HH:MM') default '12:00'
     */
    public function simulateOne(string $ticker, Carbon $nyDate, array $params)
    {
        $rangeMinutes   = (int)($params['range_minutes'] ?? 15);
        $entryBufPct    = (float)($params['entry_buffer_percent'] ?? 0.0);
        $requireRetest  = (bool)($params['require_retest'] ?? true);
        $slBufPct       = (float)($params['sl_buffer_percent'] ?? 0.0);
        $tpLevels       = (array)($params['tp_levels'] ?? [1.0, 2.0]);
        $trail          = (bool)($params['enable_trailing_stop'] ?? false);
        $sStartStr      = (string)($params['session_start'] ?? '09:30');
        $sEndStr        = (string)($params['session_end'] ?? '12:00');

        [$sh,$sm] = $this->parseTime($sStartStr);
        [$eh,$em] = $this->parseTime($sEndStr);

        $fromNY = $nyDate->copy()->setTime($sh,$sm);
        $toNY   = $nyDate->copy()->setTime($eh,$em);

        // Try symbol candidates
        $candidates = [$ticker, $ticker.'.CO', $ticker.'.ST'];
        $ohlc = null; $used = null; $err = null;

        foreach ($candidates as $sym) {
            [$ohlc, $err] = $this->fetchYahooMinuteBars($sym, $fromNY, $toNY);
            if ($ohlc && !empty($ohlc['c'])) { $used = $sym; break; }
        }
        if (!$ohlc || empty($ohlc['c']) || count($ohlc['c']) < ($rangeMinutes+5)) {
            return null;
        }

        $rangeHigh = max(array_slice($ohlc['h'], 0, $rangeMinutes));
        $rangeLow  = min(array_slice($ohlc['l'], 0, $rangeMinutes));

        $entryIdx = null;
        $entryLvl = $rangeHigh * (1.0 + $entryBufPct/100.0);

        $bars = count($ohlc['c']);
        for ($i = $rangeMinutes; $i < $bars; $i++) {
            $close = (float)$ohlc['c'][$i];
            $low   = (float)$ohlc['l'][$i];

            $retested = $low <= $rangeHigh;
            $broke    = $close > $entryLvl;

            if ($requireRetest) {
                if ($retested && $broke) { $entryIdx = $i; break; }
            } else {
                if ($broke) { $entryIdx = $i; break; }
            }
        }
        if ($entryIdx === null) return null;

        $entry = (float)$ohlc['c'][$entryIdx];

        $slFromLow = $rangeLow * (1.0 - $slBufPct/100.0);
        $sl        = max($slFromLow, $entry - max(0.01, $entry*0.0075));

        $risk = max(0.01, $entry - $sl);
        $tps = [];
        foreach ($tpLevels as $m) { $tps[] = $entry + $m*$risk; }

        $status = 'open';
        $exit   = null;
        $tpHit  = 0;
        $trailSL = $sl;

        for ($j = $entryIdx + 1; $j < $bars; $j++) {
            $price = (float)$ohlc['c'][$j];

            if ($trail && $tpHit >= 1) {
                $trailSL = max($trailSL, $entry);
                if ($price <= $trailSL) { $status='TSL'; $exit=$price; break; }
            }

            for ($k = count($tps)-1; $k >= 0; $k--) {
                if ($price >= $tps[$k]) {
                    $tpHit = max($tpHit, $k+1);
                    $status = 'TP'.($k+1);
                    if (!$trail && $k == count($tps)-1) { $exit=$price; break 2; }
                    break;
                }
            }

            if ($price <= $sl && $tpHit==0) { $status='SL'; $exit=$price; break; }
        }

        if ($status === 'open') {
            $exit = (float)$ohlc['c'][$bars-1];
            $status = 'closed';
        }

        return [
            'ticker'      => $ticker,
            'symbol'      => $used ?? $ticker,
            'date'        => $nyDate->toDateString(),
            'entry'       => $entry,
            'sl'          => $sl,
            'tps'         => $tps,
            'exit'        => $exit,
            'status'      => $status,
            'r_multiple'  => ($entry>0 && $risk>0) ? (($exit-$entry)/$risk) : null,
        ];
    }

    public function previousTradingDay(Carbon $nyNow): Carbon
    {
        $d = $nyNow->copy()->subDay();
        while ($d->isWeekend()) $d->subDay();
        return $d;
    }

    private function parseTime(string $hhmm): array
    {
        $p = explode(':', $hhmm);
        return [ (int)($p[0] ?? 9), (int)($p[1] ?? 30) ];
    }

    private function fetchYahooMinuteBars(string $symbol, Carbon $fromNY, Carbon $toNY): array
    {
        $period1 = $fromNY->timestamp;
        $period2 = $toNY->timestamp;

        $urlA = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?interval=1m&period1={$period1}&period2={$period2}&includePrePost=false";
        $respA = Http::get($urlA);

        if ($respA->successful()) {
            $data = $respA->json();
            $err  = $data['chart']['error'] ?? null;
            $res  = $data['chart']['result'][0] ?? null;
            if ($err) { return [null, $err['description'] ?? 'yahoo error A']; }
            if ($res && isset($res['indicators']['quote'][0]['close'])) {
                $q = $res['indicators']['quote'][0];
                return [[
                    'c' => $q['close'],
                    'h' => $q['high'],
                    'l' => $q['low'],
                    't' => $res['timestamp'] ?? [],
                ], null];
            }
        } else {
            return [null, "HTTP ".$respA->status()." on A"];
        }

        $urlB = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?interval=1m&range=1d&includePrePost=false";
        $respB = Http::get($urlB);
        if (!$respB->successful()) return [null, "HTTP ".$respB->status()." on B"];

        $dataB = $respB->json();
        $errB  = $dataB['chart']['error'] ?? null;
        $resB  = $dataB['chart']['result'][0] ?? null;
        if ($errB) return [null, $errB['description'] ?? 'yahoo error B'];
        if (!$resB || empty($resB['indicators']['quote'][0]['close'])) return [null, 'no quotes 1d'];

        $ts = $resB['timestamp'] ?? [];
        $q  = $resB['indicators']['quote'][0];

        $fromEpoch = $fromNY->timestamp;
        $toEpoch   = $toNY->timestamp;

        $c = $q['close']; $h = $q['high']; $l = $q['low'];
        $fc=[]; $fh=[]; $fl=[]; $ft=[];
        for ($i=0; $i<count($ts); $i++) {
            $t = (int)$ts[$i];
            if ($t >= $fromEpoch && $t <= $toEpoch) {
                $fc[] = $c[$i] ?? null;
                $fh[] = $h[$i] ?? null;
                $fl[] = $l[$i] ?? null;
                $ft[] = $t;
            }
        }
        if (count($fc) < 5) return [null, 'too few bars in window'];

        return [[ 'c'=>$fc, 'h'=>$fh, 'l'=>$fl, 't'=>$ft ], null];
    }
}

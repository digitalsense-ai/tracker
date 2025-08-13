<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\SimulatedTrade;
use App\Models\Stock;
use Carbon\Carbon;

class BacktestService
{
    public function simulate()
    {
        $results = [];
        $stocks  = Stock::all();

        // --- Load config ---
        $rangeMinutes       = (int) config('strategy.range_minutes', 15);
        $entryBufferPct     = (float) config('strategy.entry_buffer_percent', 0.00);
        $requireRetest      = (bool) config('strategy.require_retest', true);
        $slBufferPct        = (float) config('strategy.sl_buffer_percent', 0.00);
        $tpLevels           = (array) config('strategy.tp_levels', [1.0, 2.0]);
        $enableTrailing     = (bool) config('strategy.enable_trailing_stop', false);
        $maxTradesPerTicker = (int) config('strategy.max_trades_per_ticker', 1);
        $sessionStartStr    = (string) config('strategy.session_start', '09:30');
        $sessionEndStr      = (string) config('strategy.session_end', '12:00');

        // Parse session window in New York time
        [$startHour, $startMinute] = $this->parseTime($sessionStartStr);
        [$endHour, $endMinute]     = $this->parseTime($sessionEndStr);

        // previous trading day (skip weekend) in New York
        $nyNow  = Carbon::now('America/New_York');
        $nyDate = $this->previousTradingDay($nyNow);
        $fromNY = $nyDate->copy()->setTime($startHour, $startMinute);
        $toNY   = $nyDate->copy()->setTime($endHour,   $endMinute);

        Log::info("Backtest (cfg) range={$rangeMinutes} entryBuf={$entryBufferPct}% retest=".($requireRetest?'1':'0')." trail=".($enableTrailing?'1':'0')." window={$fromNY->format('H:i')}->{$toNY->format('H:i')}");
        Log::info("Backtest window (NY): ".$fromNY->toDateTimeString()." -> ".$toNY->toDateTimeString());

        foreach ($stocks as $stock) {
            $ticker = trim($stock->ticker ?? '');
            if ($ticker === '') continue;

            // only one trade per ticker/day (soft guard at service level)
            if ($maxTradesPerTicker <= 0) continue;

            // Try US, then DK (.CO), then SE (.ST)
            $candidates = [$ticker, $ticker.'.CO', $ticker.'.ST'];
            $ohlc = null; $usedSymbol = null; $errMsg = null;

            foreach ($candidates as $sym) {
                [$ohlc, $errMsg] = $this->fetchYahooMinuteBars($sym, $fromNY, $toNY);
                if ($ohlc && !empty($ohlc['c'])) { $usedSymbol = $sym; break; }
            }

            if (!$ohlc || empty($ohlc['c'])) {
                Log::warning("Backtest: no candles for {$ticker}. Last error: ".($errMsg ?? 'none'));
                continue;
            }

            $bars = count($ohlc['c']);
            if ($bars < ($rangeMinutes + 5)) {
                Log::warning("Backtest: not enough bars ({$bars}) for {$ticker} ({$usedSymbol})");
                continue;
            }

            // Opening range
            $rangeHigh = max(array_slice($ohlc['h'], 0, $rangeMinutes));
            $rangeLow  = min(array_slice($ohlc['l'], 0, $rangeMinutes));

            $entryIndex = null;
            $entryLevel = $rangeHigh * (1.0 + $entryBufferPct/100.0);

            for ($i = $rangeMinutes; $i < $bars; $i++) {
                $close = (float)$ohlc['c'][$i];
                $low   = (float)$ohlc['l'][$i];

                $retested = $low <= $rangeHigh;
                $brokeUp  = $close > $entryLevel;

                if ($requireRetest) {
                    if ($retested && $brokeUp) { $entryIndex = $i; break; }
                } else {
                    if ($brokeUp) { $entryIndex = $i; break; }
                }
            }

            if ($entryIndex === null) {
                Log::info("Backtest: no entry for {$ticker} ({$usedSymbol})");
                continue;
            }

            $entry = (float)$ohlc['c'][$entryIndex];

            // SL: ORB low (buffer applied) vs small percent floor from entry
            $slFromLow = $rangeLow * (1.0 - $slBufferPct/100.0);
            $sl        = max($slFromLow, $entry - max(0.01, $entry*0.0075)); // safety floor

            $risk = max(0.01, $entry - $sl);
            $tps  = [];
            foreach ($tpLevels as $mult) {
                $tps[] = $entry + $mult * $risk;
            }

            $status = 'open';
            $exit   = null;
            $tpHit  = 0;
            $trailSL = $sl;

            for ($j = $entryIndex + 1; $j < $bars; $j++) {
                $price = (float)$ohlc['c'][$j];

                // trailing after first TP hit
                if ($enableTrailing && $tpHit >= 1) {
                    $trailSL = max($trailSL, $entry); // move to entry after TP1
                    if ($price <= $trailSL) { $status = 'TSL'; $exit = $price; break; }
                }

                // check TPs (highest first so we record the largest hit)
                for ($k = count($tps)-1; $k >= 0; $k--) {
                    if ($price >= $tps[$k]) {
                        $tpHit = max($tpHit, $k+1);
                        $status = 'TP'.($k+1);
                        if (!$enableTrailing && $k == count($tps)-1) {
                            $exit = $price;
                            break 2;
                        }
                        break;
                    }
                }

                // SL (non-trailing or before TP1)
                if ($price <= $sl && $tpHit == 0) { $status = 'SL'; $exit = $price; break; }
            }

            // If still open at end of window: close at last
            if ($status === 'open') {
                $exit = (float)$ohlc['c'][$bars - 1];
                $status = 'closed';
            }

            // Save
            SimulatedTrade::create([
                'ticker'      => $ticker,
                'date'        => $nyDate->toDateString(),
                'entry_price' => $entry,
                'sl_price'    => $sl,
                'tp1'         => $tps[0] ?? null,
                'tp2'         => $tps[1] ?? null,
                'exit_price'  => $exit,
                'status'      => $status,
            ]);

            $results[] = [
                'ticker'     => $ticker,
                'entryPrice' => $entry,
                'slPrice'    => $sl,
                'tp1'        => $tps[0] ?? null,
                'tp2'        => $tps[1] ?? null,
                'exitPrice'  => $exit,
                'status'     => $status,
            ];

            Log::info("Backtest: {$ticker} {$usedSymbol} -> {$status} (entry={$entry}, sl={$sl}, exit={$exit}, tps=".json_encode($tps).")");
        }

        if (empty($results)) {
            Log::warning("Backtest: finished – no simulated trades generated.");
        }

        return $results;
    }

    private function parseTime(string $hhmm): array
    {
        $parts = explode(':', $hhmm);
        $h = isset($parts[0]) ? (int)$parts[0] : 9;
        $m = isset($parts[1]) ? (int)$parts[1] : 30;
        return [$h, $m];
    }

    private function previousTradingDay(Carbon $nyNow): Carbon
    {
        $d = $nyNow->copy()->subDay();
        while ($d->isWeekend()) { $d->subDay(); }
        return $d;
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

            if ($err) {
                return [null, $err['description'] ?? 'unknown yahoo error (A)'];
            }
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

        // Fallback: range=1d and filter
        $urlB = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}?interval=1m&range=1d&includePrePost=false";
        $respB = Http::get($urlB);

        if (!$respB->successful()) {
            return [null, "HTTP ".$respB->status()." on B"];
        }

        $dataB = $respB->json();
        $errB  = $dataB['chart']['error'] ?? null;
        $resB  = $dataB['chart']['result'][0] ?? null;

        if ($errB) {
            return [null, $errB['description'] ?? 'unknown yahoo error (B)'];
        }
        if (!$resB || empty($resB['indicators']['quote'][0]['close'])) {
            return [null, 'no quote data in range=1d'];
        }

        $ts = $resB['timestamp'] ?? [];
        $q  = $resB['indicators']['quote'][0];

        $fromEpoch = $fromNY->timestamp;
        $toEpoch   = $toNY->timestamp;

        $c = $q['close']; $h = $q['high']; $l = $q['low'];
        $fc = []; $fh = []; $fl = []; $ft = [];

        for ($i = 0; $i < count($ts); $i++) {
            $t = (int)$ts[$i];
            if ($t >= $fromEpoch && $t <= $toEpoch) {
                $fc[] = $c[$i] ?? null;
                $fh[] = $h[$i] ?? null;
                $fl[] = $l[$i] ?? null;
                $ft[] = $t;
            }
        }

        if (count($fc) < 5) {
            return [null, 'filtered window has too few bars'];
        }

        return [[ 'c'=>$fc, 'h'=>$fh, 'l'=>$fl, 't'=>$ft ], null];
    }
}

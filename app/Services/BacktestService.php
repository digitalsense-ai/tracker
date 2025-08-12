<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\SimulatedTrade;
use App\Models\Stock;
use Carbon\Carbon;

class BacktestService
{
    // Tunables
    private float $slPercentFloor = 0.0075;   // 0.75% below entry (floor)
    private int   $orbMinutes     = 15;       // opening range length
    private int   $nyStartHour    = 9;
    private int   $nyStartMinute  = 30;
    private int   $nyEndHour      = 12;       // extend to 12:00 ET
    private int   $nyEndMinute    = 0;

    public function simulate()
    {
        $results = [];
        $stocks  = Stock::all();

        // previous trading day (skip weekend) in New York
        $nyNow  = Carbon::now('America/New_York');
        $nyDate = $this->previousTradingDay($nyNow);
        $fromNY = $nyDate->copy()->setTime($this->nyStartHour, $this->nyStartMinute);
        $toNY   = $nyDate->copy()->setTime($this->nyEndHour,   $this->nyEndMinute);

        Log::info("Backtest window (NY): ".$fromNY->toDateTimeString()." -> ".$toNY->toDateTimeString());

        foreach ($stocks as $stock) {
            $ticker = trim($stock->ticker ?? '');
            if ($ticker === '') continue;

            // try US, then DK (.CO), then SE (.ST)
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
            if ($bars < ($this->orbMinutes + 5)) {
                Log::warning("Backtest: not enough bars ({$bars}) for {$ticker} ({$usedSymbol})");
                continue;
            }

            // Opening range (first N minutes)
            $high = max(array_slice($ohlc['h'], 0, $this->orbMinutes));
            $low  = min(array_slice($ohlc['l'], 0, $this->orbMinutes));

            // Retest + breakout over ORB high
            $entryIndex = null;
            for ($i = $this->orbMinutes; $i < $bars; $i++) {
                $retested = ((float)$ohlc['l'][$i]) <= (float)$high;
                $brokeUp  = ((float)$ohlc['c'][$i]) >  (float)$high;
                if ($retested && $brokeUp) { $entryIndex = $i; break; }
            }

            if ($entryIndex === null) {
                Log::info("Backtest: no retest+breakout for {$ticker} ({$usedSymbol})");
                continue;
            }

            $entry   = (float)$ohlc['c'][$entryIndex];
            // Tightened SL: use max(ORB low, entry * (1 - slPercentFloor))
            $slFloor = $entry * (1.0 - $this->slPercentFloor);
            $sl      = max((float)$low, $slFloor);

            $risk = max(0.01, $entry - $sl);
            $tp1  = $entry + $risk;
            $tp2  = $entry + 2 * $risk;

            $status = 'open';
            $exit   = null;

            for ($j = $entryIndex + 1; $j < $bars; $j++) {
                $price = (float)$ohlc['c'][$j];
                if ($price <= $sl) { $status = 'SL';  $exit = $price; break; }
                if ($price >= $tp2){ $status = 'TP2'; $exit = $price; break; }
                if ($price >= $tp1){ $status = 'TP1'; /* keep scanning for TP2 */ }
            }

            // If still open at end of window: close at last price
            if ($status === 'open') {
                $exit = (float)$ohlc['c'][$bars - 1];
                $status = 'closed';
            }

            SimulatedTrade::create([
                'ticker'      => $ticker,
                'date'        => $nyDate->toDateString(),
                'entry_price' => $entry,
                'sl_price'    => $sl,
                'tp1'         => $tp1,
                'tp2'         => $tp2,
                'exit_price'  => $exit,
                'status'      => $status,
            ]);

            $results[] = [
                'ticker'     => $ticker,
                'entryPrice' => $entry,
                'slPrice'    => $sl,
                'tp1'        => $tp1,
                'tp2'        => $tp2,
                'exitPrice'  => $exit,
                'status'     => $status,
            ];

            Log::info("Backtest: {$ticker} {$usedSymbol} -> {$status} (entry={$entry}, sl={$sl}, tp1={$tp1}, tp2={$tp2}, exit={$exit})");
        }

        if (empty($results)) {
            Log::warning("Backtest: finished – no simulated trades generated.");
        }

        return $results;
    }

    private function previousTradingDay(Carbon $nyNow): Carbon
    {
        $d = $nyNow->copy()->subDay();
        while ($d->isWeekend()) $d->subDay();
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

        // Fallback: range=1d and filter 9:30–end window
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

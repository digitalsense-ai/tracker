<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\SimulatedTrade;
use App\Models\Stock;
use Carbon\Carbon;

class BacktestService
{
    /**
     * Backward compatible: run for the last $days days (yesterday = 1).
     */
    public function simulate(int $days = 1): array
    {
        $results = [];
        $todayNy = Carbon::now('America/New_York')->startOfDay();

        for ($d = 1; $d <= $days; $d++) {
            $dateStr = $todayNy->copy()->subDays($d)->format('Y-m-d');
            $out = $this->simulateForDate($dateStr);
            if (!empty($out)) {
                $results = array_merge($results, $out);
            }
        }
        return $results;
    }

    /**
     * Single-date simulation (YYYY-MM-DD).
     */
    public function simulateForDate(string $dateStr): array
    {
        $results = [];

        // --- Config (safe defaults) ---
        $feePercent     = (float) config('strategy.fee_percent', 0.0008);      // 0.08%
        $feeMinPerOrder = (float) config('strategy.fee_min_per_order', 2.0);   // $2 minimum
        $sessionStart   = (string) config('strategy.session_start', '09:30');
        $sessionEnd     = (string) config('strategy.session_end',   '16:00');
        $rangeMinutes   = (int)    config('strategy.range_minutes', 15);
        $entryBufPct    = (float)  config('strategy.entry_buffer_percent', 0.0);
        $requireRetest  = (bool)   config('strategy.require_retest', true);
        $slBufPct       = (float)  config('strategy.sl_buffer_percent', 0.0);
        $positionUsd    = (float)  config('strategy.position_usd', 1000);

        // --- Build exact NY timestamps ---
        $fromTs = Carbon::parse($dateStr.' '.$sessionStart, 'America/New_York')->timestamp;
        $toTs   = Carbon::parse($dateStr.' '.$sessionEnd,   'America/New_York')->timestamp;

        $stocks = Stock::all();

        foreach ($stocks as $stock) {
            $ticker = (string) $stock->ticker;
            if ($ticker === '') continue;

            $symbol = $this->resolveYahooSymbol($ticker);

            try {
                Log::info("V4 Backtest: {$ticker} @ {$dateStr} symbol={$symbol}");

                $response = Http::timeout(20)->get(
                    "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}",
                    [
                        'interval'       => '1m',
                        'period1'        => $fromTs,
                        'period2'        => $toTs,
                        'includePrePost' => 'false',
                    ]
                );

                if (!$response->successful()) {
                    Log::warning("Yahoo fetch failed for {$symbol} {$dateStr}: HTTP ".$response->status());
                    continue;
                }

                $data   = $response->json();
                $result = $data['chart']['result'][0] ?? null;
                $q      = $result['indicators']['quote'][0] ?? null;

                // Defensive: ensure arrays exist & contain numbers
                if (!$q
                    || empty($q['close']) || empty($q['high']) || empty($q['low'])
                    || !is_array($q['close']) || !is_array($q['high']) || !is_array($q['low'])
                ) {
                    Log::warning("No/invalid candles for {$symbol} {$dateStr}");
                    continue;
                }

                $close = array_values(array_filter($q['close'], 'is_numeric'));
                $high  = array_values(array_filter($q['high'],  'is_numeric'));
                $low   = array_values(array_filter($q['low'],   'is_numeric'));

                $nBars = min(count($close), count($high), count($low));
                if ($nBars <= $rangeMinutes + 1) {
                    Log::warning("Too few candles for {$symbol} {$dateStr} (n={$nBars})");
                    continue;
                }

                // ORB window (first N minutes)
                $orbHigh = max(array_slice($high, 0, $rangeMinutes));
                $orbLow  = min(array_slice($low,  0, $rangeMinutes));
                $breakoutLevel = $orbHigh * (1.0 + $entryBufPct / 100.0);
                $slLevel       = $orbLow  * (1.0 - $slBufPct   / 100.0);

                $entryIdx   = null;
                $entryPrice = null;
                $exitPrice  = null;
                $inPos      = false;
                $exited     = false;
                $status     = 'open';
                $tp1 = null; $tp2 = null; $r = null;

                for ($i = $rangeMinutes; $i < $nBars; $i++) {
                    if ($entryIdx === null) {
                        // breakout
                        if ((float)$close[$i] > (float)$breakoutLevel) {
                            // optional retest of ORB high
                            if ($requireRetest) {
                                $retested = false;
                                for ($j = $rangeMinutes; $j <= $i; $j++) {
                                    if ((float)$low[$j] <= (float)$orbHigh) { $retested = true; break; }
                                }
                                if (!$retested) continue;
                            }
                            $entryIdx   = $i;
                            $entryPrice = (float) $close[$i];
                            if ($entryPrice <= 0) { $entryIdx = null; continue; }

                            $inPos = true;
                            $r  = max(0.0001, (float)$entryPrice - (float)$slLevel);
                            $tp1 = (float)$entryPrice + $r;
                            $tp2 = (float)$entryPrice + 2.0 * $r;
                        }
                    } else {
                        // manage TP/SL
                        if ((float)$low[$i] <= (float)$slLevel) {
                            $exitPrice = (float) $slLevel;
                            $status    = 'SL';
                            $exited    = true;
                            break;
                        }
                        if ($tp2 !== null && (float)$close[$i] >= (float)$tp2) {
                            $exitPrice = (float) $tp2;
                            $status    = 'TP2';
                            $exited    = true;
                            break;
                        }
                        if ($tp1 !== null && (float)$close[$i] >= (float)$tp1) {
                            $status = 'TP1'; // keep position for TP2/EOD
                        }
                    }
                }

                // EOD exit if still in position
                if ($inPos && !$exited) {
                    $last = end($close);
                    if (!is_numeric($last)) {
                        $last = $close[count($close)-1] ?? null;
                    }
                    if (is_numeric($last)) {
                        $exitPrice = (float) $last;
                        $status    = 'closed';
                        $exited    = true;
                    }
                }

                if ($entryIdx !== null && is_numeric($entryPrice)) {
                    $shares = $this->sharesFromPosition($positionUsd, (float)$entryPrice);
                    if ($shares <= 0) {
                        Log::warning("Shares=0 for {$ticker} {$dateStr} at price {$entryPrice}");
                        continue;
                    }

                    $fees = $this->calcFees((float)$entryPrice, $exitPrice, $shares, $feePercent, $feeMinPerOrder);
                    $net  = (is_numeric($exitPrice))
                        ? (($exitPrice - (float)$entryPrice) * $shares - $fees)
                        : null;

                    SimulatedTrade::updateOrCreate(
                        ['ticker' => $ticker, 'date' => $dateStr],
                        [
                            'entry_price' => (float)$entryPrice,
                            'exit_price'  => is_numeric($exitPrice) ? (float)$exitPrice : null,
                            'sl_price'    => (float)$slLevel,
                            'tp1'         => is_numeric($tp1) ? (float)$tp1 : null,
                            'tp2'         => is_numeric($tp2) ? (float)$tp2 : null,
                            'status'      => $status,
                            'fees'        => (float)$fees,
                            'net_profit'  => is_numeric($net) ? (float)$net : null,
                            'forecast_type'  => $stock->forecast ?? 'orb-retest',
                            'forecast_score' => $stock->forecast_score ?? null,
                            'trend_rating'   => $stock->trend_rating ?? null,
                            'executed_on_nordnet' => false,
                        ]
                    );

                    $results[] = [
                        'ticker' => $ticker,
                        'date'   => $dateStr,
                        'entry'  => (float)$entryPrice,
                        'exit'   => is_numeric($exitPrice) ? (float)$exitPrice : null,
                        'sl'     => (float)$slLevel,
                        'status' => $status,
                        'net'    => is_numeric($net) ? (float)$net : null,
                    ];
                }

            } catch (\Throwable $e) {
                Log::error("Backtest error {$ticker} {$dateStr}: ".$e->getMessage());
                continue;
            }
        }

        return $results;
    }

    private function resolveYahooSymbol(string $ticker): string
    {
        // US tickers: no suffix; later add .CO/.ST/.OL via config if needed
        return $ticker;
    }

    private function sharesFromPosition(float $usd, float $price): int
    {
        if ($price <= 0) return 0;
        return max(1, (int) floor($usd / $price));
    }

    private function calcFees(float $entry, ?float $exit, int $shares, float $pct, float $min): float
    {
        $buy  = max($min, $entry * $shares * $pct);
        $sell = ($exit !== null && is_numeric($exit)) ? max($min, $exit * $shares * $pct) : 0.0;
        return round($buy + $sell, 2);
    }
}

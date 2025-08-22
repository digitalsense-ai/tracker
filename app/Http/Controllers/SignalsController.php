<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\SimulatedTrade;
use App\Models\Stock;

class SignalsController extends Controller
{
    /**
     * Signals page:
     * - Shows step-by-step status (Data → ORB → Breakout → Retest → Confirm → Outcome) for MANY tickers for a given date.
     * - Primary source = simulated_trades (fast). If empty, optionally falls back to BacktestService per ticker (slow).
     */
    public function index(Request $request)
    {
        $date = $request->input('date', now('America/New_York')->subWeekday(1)->toDateString());
        $source = $request->input('source', 'db'); // db | service
        $rows = [];

        if ($source === 'db') {
            $rows = $this->fromDb($date);
            if (empty($rows)) {
                $rows = $this->fromService($date); // fallback if no data in DB
                $source = 'service';
            }
        } else {
            $rows = $this->fromService($date);
        }

        // sort: winners first, then others
        usort($rows, function($a, $b){
            $ordA = $this->rankOutcome($a['outcome'] ?? '');
            $ordB = $this->rankOutcome($b['outcome'] ?? '');
            if ($ordA === $ordB) return strcmp($a['ticker'], $b['ticker']);
            return $ordA <=> $ordB;
        });

        return view('signals', [
            'date'   => $date,
            'source' => $source,
            'rows'   => $rows,
        ]);
    }

    private function rankOutcome(string $o): int
    {
        // Better to worse
        return match($o) {
            'TP2' => 1,
            'TP1' => 2,
            'closed' => 3,
            'open' => 4,
            'SL' => 99,
            default => 50,
        };
    }

    private function fromDb(string $date): array
    {
        $rows = [];
        $trades = SimulatedTrade::whereDate('date', $date)->get();
        foreach ($trades as $t) {
            $risk = (is_numeric($t->entry_price) && is_numeric($t->sl_price)) ? ($t->entry_price - $t->sl_price) : null;
            $tp1  = (is_numeric($t->entry_price) && is_numeric($risk)) ? $t->entry_price + $risk : null;
            $tp2  = (is_numeric($t->entry_price) && is_numeric($risk)) ? $t->entry_price + 2.0 * $risk : null;

            $rows[] = [
                'ticker'   => $t->ticker,
                'data_ok'  => true,              // data existed in DB
                'orb_ok'   => true,              // implied (we had ORB computed during sim)
                'breakout' => (bool) is_numeric($t->entry_price),
                'retest'   => true,              // implied by our sim logic (require_retest)
                'confirm'  => (bool) is_numeric($t->entry_price),
                'entry'    => $t->entry_price,
                'sl'       => $t->sl_price,
                'tp1'      => $tp1,
                'tp2'      => $tp2,
                'exit'     => $t->exit_price,
                'outcome'  => $t->status,
                'net'      => $t->net_profit,
            ];
        }
        return $rows;
    }

    private function fromService(string $date): array
    {
        $rows = [];
        try {
            if (!class_exists(\App\Services\BacktestService::class)) {
                return $rows;
            }
            $svc = app(\App\Services\BacktestService::class);

            // Choose tickers
            $tickers = [];
            if (class_exists(\App\Models\Stock::class)) {
                $tickers = Stock::query()->pluck('ticker')->all();
            }

            foreach ($tickers as $ticker) {
                try {
                    if (method_exists($svc, 'simulateForDate')) {
                        $res = $svc->simulateForDate($date);
                        // simulateForDate returns MANY tickers; extract this ticker
                        if (is_array($res)) {
                            foreach ($res as $r) {
                                if (($r['ticker'] ?? null) === $ticker) {
                                    $rows[] = [
                                        'ticker'   => $ticker,
                                        'data_ok'  => true,
                                        'orb_ok'   => true,
                                        'breakout' => (null !== ($value = $r['entry'] ?? $r['entry_price'] ?? null)),
                                        'retest'   => true, // our strategy requires retest
                                        'confirm'  => (null !== ($value = $r['entry'] ?? $r['entry_price'] ?? null)),
                                        'entry'    => $r['entry'] ?? $r['entry_price'] ?? null,
                                        'sl'       => $r['sl'] ?? $r['sl_price'] ?? null,
                                        'tp1'      => $r['tp1'] ?? null,
                                        'tp2'      => $r['tp2'] ?? null,
                                        'exit'     => $r['exit'] ?? $r['exit_price'] ?? null,
                                        'outcome'  => $r['status'] ?? null,
                                        'net'      => $r['net'] ?? $r['net_profit'] ?? null,
                                    ];
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("Signals per ticker failed: {$ticker} {$date}: ".$e->getMessage());
                    continue;
                }
            }
        } catch (\Throwable $e) {
            Log::error("Signals fallback failed: ".$e->getMessage());
        }
        return $rows;
    }
}

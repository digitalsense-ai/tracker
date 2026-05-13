<?php

namespace App\Services;

use Carbon\Carbon;

class BacktestService
{
    /**
     * Adapter method: tries common simulate() signatures.
     */
    public function simulateForDate(Carbon $startDate, int $days = 1, array $options = [])
    {
        $days = max(1, (int) $days);
        $from = $startDate->copy()->startOfDay();
        $to   = $startDate->copy()->addDays($days)->endOfDay();

        $optWithDates = array_merge($options, [
            'date_from' => $from,
            'date_to'   => $to,
        ]);

        if (!method_exists($this, 'simulate')) {
            throw new \BadMethodCallException('simulate() does not exist in BacktestService.');
        }

        $attempts = [
            function () use ($days, $optWithDates) { return $this->simulate($days, $optWithDates); },
            function () use ($days) { return $this->simulate($days); },
            function () use ($from, $to, $options) { return $this->simulate($from, $to, $options); },
            function () use ($from, $to) { return $this->simulate($from, $to); },
            function () use ($optWithDates) { return $this->simulate($optWithDates); },
            function () { return $this->simulate(); },
        ];

        $errors = [];
        foreach ($attempts as $i => $call) {
            try {
                return $call();
            } catch (\ArgumentCountError $e) {
                $errors[] = 'Attempt #' . ($i + 1) . ' ArgumentCountError: ' . $e->getMessage();
                continue;
            } catch (\TypeError $e) {
                $errors[] = 'Attempt #' . ($i + 1) . ' TypeError: ' . $e->getMessage();
                continue;
            } catch (\Throwable $e) {
                throw $e;
            }
        }

        $hint = implode(' | ', $errors);
        throw new \BadMethodCallException('Could not call simulate() with any known signature. Attempts: ' . $hint);
    }

    /**
     * Resolve long-trade exit behavior using the same deterministic TP policy
     * model as PaperBroker.
     *
     * Supported TP models:
     * - full_exit: target closes the full remaining position
     * - simple_runner: target triggers TP1 partial, SL moves to BE, runner trails
     * - no_tp: target is ignored and only stop/trailing/manual logic applies
     *
     * Conservative same-candle rule: stop checks before target/trigger.
     *
     * Returns an array with keys:
     * - status: open|closed
     * - exit_reason: stop_loss|take_profit|trailing_stop|tp1_partial|target_trigger|null
     * - exit_price: float|null
     * - realized_pnl_r: float realized R multiple for this candle/event
     * - remaining_qty_pct: float remaining position fraction
     * - tp1_hit: bool
     * - trailing_active: bool
     * - stop_price: float
     * - highest_price: float
     */
    public function resolveLongExit(array $trade, array $candle): array
    {
        $entry = (float) ($trade['entry_price'] ?? 0.0);
        $stop = (float) ($trade['stop_price'] ?? 0.0);
        $initialStop = (float) ($trade['initial_stop_price'] ?? $stop);
        $risk = (float) ($trade['risk'] ?? max(0.0, $entry - $initialStop));

        $tpModel = (string) ($trade['tp_model'] ?? config('strategy.tp_model', 'simple_runner'));
        if (!in_array($tpModel, ['full_exit', 'simple_runner', 'no_tp'], true)) {
            $tpModel = 'simple_runner';
        }

        $tpEnabled = (bool) ($trade['take_profit_enabled'] ?? config('strategy.take_profit_enabled', true));
        $tpRr = (float) ($trade['take_profit_rr'] ?? $trade['tp1_rr'] ?? config('strategy.take_profit_rr', 1.0));
        $target = (float) ($trade['target_price'] ?? ($entry + ($risk * $tpRr)));

        $remainingQtyPct = (float) ($trade['remaining_qty_pct'] ?? $trade['remaining_size'] ?? 1.0);
        $remainingQtyPct = max(0.0, min(1.0, $remainingQtyPct));

        $tp1Hit = (bool) ($trade['tp1_hit'] ?? false);
        $trailingActive = (bool) ($trade['trailing_active'] ?? $trade['runner_active'] ?? false);
        $highest = (float) ($trade['highest_price'] ?? $entry);
        $trailDistanceRr = (float) ($trade['runner_trail_distance_rr'] ?? config('strategy.runner_trail_distance_rr', 1.0));
        $trailDistance = (float) ($trade['trail_distance'] ?? ($risk * $trailDistanceRr));
        $tp1ClosePct = max(0.0, min(1.0, (float) ($trade['tp1_close_pct'] ?? config('strategy.tp1_close_pct', 0.5))));
        $moveSlToBe = (bool) ($trade['move_sl_to_break_even_on_tp1'] ?? config('strategy.move_sl_to_break_even_on_tp1', true));
        $runnerTrailingEnabled = (bool) ($trade['runner_trailing_enabled'] ?? config('strategy.runner_trailing_enabled', true));

        $high = (float) ($candle['high'] ?? 0.0);
        $low = (float) ($candle['low'] ?? 0.0);

        $highest = max($highest, $high);

        // Conservative same-candle rule: stop checks before target/trigger.
        if ($low <= $stop) {
            return [
                'status' => 'closed',
                'exit_reason' => $trailingActive ? 'trailing_stop' : 'stop_loss',
                'exit_price' => $stop,
                'realized_pnl_r' => $risk > 0 ? (($stop - $entry) / $risk) * $remainingQtyPct : 0.0,
                'remaining_qty_pct' => 0.0,
                'tp1_hit' => $tp1Hit,
                'trailing_active' => $trailingActive,
                'stop_price' => $stop,
                'highest_price' => $highest,
                'target_price' => $target,
            ];
        }

        if (!$tpEnabled || $tpModel === 'no_tp') {
            return $this->resolveLongTrailingOnly($entry, $stop, $risk, $high, $low, $highest, $trailDistance, $remainingQtyPct, $tp1Hit, $trailingActive, $target);
        }

        if ($tpModel === 'full_exit') {
            if ($high >= $target) {
                return [
                    'status' => 'closed',
                    'exit_reason' => 'take_profit',
                    'exit_price' => $target,
                    'realized_pnl_r' => $risk > 0 ? (($target - $entry) / $risk) * $remainingQtyPct : 0.0,
                    'remaining_qty_pct' => 0.0,
                    'tp1_hit' => $tp1Hit,
                    'trailing_active' => false,
                    'stop_price' => $stop,
                    'highest_price' => $highest,
                    'target_price' => $target,
                ];
            }

            return [
                'status' => 'open',
                'exit_reason' => null,
                'exit_price' => null,
                'realized_pnl_r' => 0.0,
                'remaining_qty_pct' => $remainingQtyPct,
                'tp1_hit' => $tp1Hit,
                'trailing_active' => false,
                'stop_price' => $stop,
                'highest_price' => $highest,
                'target_price' => $target,
            ];
        }

        // simple_runner: TP1 is a partial profit trigger, not final exit.
        if (!$tp1Hit && $high >= $target) {
            $closedQtyPct = min($remainingQtyPct, $remainingQtyPct * $tp1ClosePct);
            $remainingQtyPct = max(0.0, $remainingQtyPct - $closedQtyPct);
            $tp1Hit = true;
            $trailingActive = $runnerTrailingEnabled;

            if ($moveSlToBe) {
                $stop = max($stop, $entry);
            }

            return [
                'status' => $remainingQtyPct <= 0 ? 'closed' : 'open',
                'exit_reason' => 'tp1_partial',
                'exit_price' => $target,
                'realized_pnl_r' => $risk > 0 ? (($target - $entry) / $risk) * $closedQtyPct : 0.0,
                'remaining_qty_pct' => $remainingQtyPct,
                'tp1_hit' => $tp1Hit,
                'trailing_active' => $trailingActive,
                'stop_price' => $stop,
                'highest_price' => $highest,
                'target_price' => $target,
            ];
        }

        if ($trailingActive && $runnerTrailingEnabled) {
            $stop = max($stop, $highest - $trailDistance);

            if ($low <= $stop) {
                return [
                    'status' => 'closed',
                    'exit_reason' => 'trailing_stop',
                    'exit_price' => $stop,
                    'realized_pnl_r' => $risk > 0 ? (($stop - $entry) / $risk) * $remainingQtyPct : 0.0,
                    'remaining_qty_pct' => 0.0,
                    'tp1_hit' => $tp1Hit,
                    'trailing_active' => true,
                    'stop_price' => $stop,
                    'highest_price' => $highest,
                    'target_price' => $target,
                ];
            }
        }

        return [
            'status' => 'open',
            'exit_reason' => $trailingActive ? 'target_trigger' : null,
            'exit_price' => null,
            'realized_pnl_r' => 0.0,
            'remaining_qty_pct' => $remainingQtyPct,
            'tp1_hit' => $tp1Hit,
            'trailing_active' => $trailingActive,
            'stop_price' => $stop,
            'highest_price' => $highest,
            'target_price' => $target,
        ];
    }

    protected function resolveLongTrailingOnly(
        float $entry,
        float $stop,
        float $risk,
        float $high,
        float $low,
        float $highest,
        float $trailDistance,
        float $remainingQtyPct,
        bool $tp1Hit,
        bool $trailingActive,
        float $target
    ): array {
        if ($trailingActive) {
            $stop = max($stop, $highest - $trailDistance);

            if ($low <= $stop) {
                return [
                    'status' => 'closed',
                    'exit_reason' => 'trailing_stop',
                    'exit_price' => $stop,
                    'realized_pnl_r' => $risk > 0 ? (($stop - $entry) / $risk) * $remainingQtyPct : 0.0,
                    'remaining_qty_pct' => 0.0,
                    'tp1_hit' => $tp1Hit,
                    'trailing_active' => true,
                    'stop_price' => $stop,
                    'highest_price' => $highest,
                    'target_price' => $target,
                ];
            }
        }

        return [
            'status' => 'open',
            'exit_reason' => null,
            'exit_price' => null,
            'realized_pnl_r' => 0.0,
            'remaining_qty_pct' => $remainingQtyPct,
            'tp1_hit' => $tp1Hit,
            'trailing_active' => $trailingActive,
            'stop_price' => $stop,
            'highest_price' => $highest,
            'target_price' => $target,
        ];
    }
}

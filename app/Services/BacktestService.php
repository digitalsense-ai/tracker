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
     * Resolve long-trade exit behavior using the canonical rules:
     * - trailing OFF: target is a final exit
     * - trailing ON: target activates runner mode and stop moves to break-even
     * - conservative same-candle rule: stop wins ties
     *
     * Returns an array with keys:
     * - status: open|closed
     * - exit_reason: stop_loss|take_profit|trailing_stop|target_trigger
     * - exit_price: float|null
     * - trailing_active: bool
     * - stop_price: float
     * - highest_price: float
     */
    public function resolveLongExit(array $trade, array $candle): array
    {
        $entry = (float) ($trade['entry_price'] ?? 0.0);
        $stop = (float) ($trade['stop_price'] ?? 0.0);
        $risk = (float) ($trade['risk'] ?? max(0.0, $entry - $stop));
        $tpRr = (float) ($trade['take_profit_rr'] ?? config('strategy.take_profit_rr', 2));
        $target = (float) ($trade['target_price'] ?? ($entry + ($risk * $tpRr)));
        $trailingEnabled = (bool) ($trade['enable_trailing_stop'] ?? config('strategy.enable_trailing_stop', false));
        $trailingActive = (bool) ($trade['trailing_active'] ?? false);
        $highest = (float) ($trade['highest_price'] ?? $entry);
        $trailDistance = (float) ($trade['trail_distance'] ?? $risk);

        $high = (float) ($candle['high'] ?? 0.0);
        $low = (float) ($candle['low'] ?? 0.0);

        $highest = max($highest, $high);

        // Conservative same-candle rule: stop checks before target/trigger.
        if ($low <= $stop) {
            return [
                'status' => 'closed',
                'exit_reason' => $trailingActive ? 'trailing_stop' : 'stop_loss',
                'exit_price' => $stop,
                'trailing_active' => $trailingActive,
                'stop_price' => $stop,
                'highest_price' => $highest,
                'target_price' => $target,
            ];
        }

        if (!$trailingEnabled) {
            if ($high >= $target) {
                return [
                    'status' => 'closed',
                    'exit_reason' => 'take_profit',
                    'exit_price' => $target,
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
                'trailing_active' => false,
                'stop_price' => $stop,
                'highest_price' => $highest,
                'target_price' => $target,
            ];
        }

        if (!$trailingActive && $high >= $target) {
            $trailingActive = true;
            $stop = max($stop, $entry); // move to break-even on trigger
        }

        if ($trailingActive) {
            $stop = max($stop, $highest - $trailDistance);

            if ($low <= $stop) {
                return [
                    'status' => 'closed',
                    'exit_reason' => 'trailing_stop',
                    'exit_price' => $stop,
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
            'trailing_active' => $trailingActive,
            'stop_price' => $stop,
            'highest_price' => $highest,
            'target_price' => $target,
        ];
    }
}

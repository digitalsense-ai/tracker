<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\Position;
use App\Models\StrategyProfile;

class TakeProfitPolicy
{
    /**
     * Resolve a deterministic TP policy from the most specific source available.
     * Priority:
     * 1. Position-level persisted policy
     * 2. AI model overrides
     * 3. Strategy profile settings[management]
     * 4. Daily plan item fields
     * 5. Global config defaults
     */
    public static function resolve(
        ?Position $position = null,
        ?AiModel $model = null,
        ?StrategyProfile $profile = null,
        array $planItem = []
    ): array {
        $model = $model ?: $position?->model;
        $management = is_array($profile?->settings ?? null)
            ? ($profile->settings['management'] ?? [])
            : [];

        $policy = [
            'take_profit_enabled' => self::boolValue(
                $position?->take_profit_enabled
                    ?? $model?->take_profit_enabled
                    ?? $planItem['take_profit_enabled']
                    ?? $management['take_profit_enabled']
                    ?? config('strategy.take_profit_enabled', true)
            ),
            'tp_model' => (string) (
                $position?->tp_model
                    ?? $model?->tp_model
                    ?? $planItem['tp_model']
                    ?? $management['tp_model']
                    ?? config('strategy.tp_model', 'simple_runner')
            ),
            'tp1_rr' => self::floatValue(
                $model?->tp1_rr
                    ?? $planItem['tp1_rr']
                    ?? $management['tp1_rr']
                    ?? $management['tp1_r']
                    ?? config('strategy.take_profit_rr', 1.0),
                1.0
            ),
            'tp1_close_pct' => self::clampPct(self::floatValue(
                $position?->tp1_close_pct
                    ?? $model?->tp1_close_pct
                    ?? $planItem['tp1_close_pct']
                    ?? $management['tp1_close_pct']
                    ?? config('strategy.tp1_close_pct', 0.5),
                0.5
            )),
            'move_sl_to_break_even_on_tp1' => self::boolValue(
                $position?->move_sl_to_break_even_on_tp1
                    ?? $model?->move_sl_to_break_even_on_tp1
                    ?? $planItem['move_sl_to_break_even_on_tp1']
                    ?? $management['move_sl_to_break_even_on_tp1']
                    ?? config('strategy.move_sl_to_break_even_on_tp1', true)
            ),
            'runner_trailing_enabled' => self::boolValue(
                $position?->runner_trailing_enabled
                    ?? $model?->runner_trailing_enabled
                    ?? $planItem['runner_trailing_enabled']
                    ?? $management['runner_trailing_enabled']
                    ?? $management['use_trailing']
                    ?? config('strategy.runner_trailing_enabled', true)
            ),
            'runner_trail_distance_rr' => self::floatValue(
                $position?->runner_trail_distance_rr
                    ?? $model?->runner_trail_distance_rr
                    ?? $planItem['runner_trail_distance_rr']
                    ?? $management['runner_trail_distance_rr']
                    ?? config('strategy.runner_trail_distance_rr', 1.0),
                1.0
            ),
        ];

        if (!in_array($policy['tp_model'], ['full_exit', 'simple_runner', 'no_tp'], true)) {
            $policy['tp_model'] = 'simple_runner';
        }

        return $policy;
    }

    public static function persistOnPosition(Position $position, array $policy): void
    {
        $position->tp_model = $policy['tp_model'] ?? config('strategy.tp_model', 'simple_runner');
        $position->tp1_close_pct = $policy['tp1_close_pct'] ?? config('strategy.tp1_close_pct', 0.5);
        $position->move_sl_to_break_even_on_tp1 = $policy['move_sl_to_break_even_on_tp1'] ?? true;
        $position->runner_trailing_enabled = $policy['runner_trailing_enabled'] ?? true;
        $position->runner_trail_distance_rr = $policy['runner_trail_distance_rr'] ?? 1.0;
    }

    protected static function boolValue($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected static function floatValue($value, float $default): float
    {
        return is_numeric($value) ? (float) $value : $default;
    }

    protected static function clampPct(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }
}

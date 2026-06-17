<?php

namespace App\Services\Risk;

use App\DTO\AI\RiskGuardResult;

class RiskGuardService
{
    /**
     * Observe-only RiskGuard v1.
     *
     * This service evaluates whether a recommendation would be blocked
     * by hard safety constraints. It does not execute actions.
     *
     * Supported context keys:
     * - kill_switch_active: bool
     * - daily_loss_limit_reached: bool
     * - model_loss_limit_reached: bool
     * - max_open_trades_reached: bool
     * - portfolio_heat: low|medium|high|critical
     * - spread_ok: bool
     * - liquidity_ok: bool
     */
    public function evaluate(array $context = []): RiskGuardResult
    {
        $blockedBy = [];
        $warnings = [];
        $reasonCodes = [];

        if ((bool) ($context['kill_switch_active'] ?? false)) {
            $blockedBy[] = 'kill_switch_active';
            $reasonCodes[] = 'blocked_by_kill_switch';
        }

        if ((bool) ($context['daily_loss_limit_reached'] ?? false)) {
            $blockedBy[] = 'daily_loss_limit_reached';
            $reasonCodes[] = 'blocked_by_daily_loss_limit';
        }

        if ((bool) ($context['model_loss_limit_reached'] ?? false)) {
            $blockedBy[] = 'model_loss_limit_reached';
            $reasonCodes[] = 'blocked_by_model_loss_limit';
        }

        if ((bool) ($context['max_open_trades_reached'] ?? false)) {
            $warnings[] = 'max_open_trades_reached';
            $reasonCodes[] = 'open_trade_capacity_warning';
        }

        if (($context['portfolio_heat'] ?? null) === 'critical') {
            $blockedBy[] = 'portfolio_heat_critical';
            $reasonCodes[] = 'blocked_by_portfolio_heat';
        } elseif (($context['portfolio_heat'] ?? null) === 'high') {
            $warnings[] = 'portfolio_heat_high';
            $reasonCodes[] = 'portfolio_heat_warning';
        }

        if (($context['spread_ok'] ?? true) === false) {
            $warnings[] = 'spread_not_ok';
            $reasonCodes[] = 'spread_warning';
        }

        if (($context['liquidity_ok'] ?? true) === false) {
            $warnings[] = 'liquidity_not_ok';
            $reasonCodes[] = 'liquidity_warning';
        }

        $approved = $blockedBy === [];

        return new RiskGuardResult(
            approved: $approved,
            decision: $approved ? 'allow' : 'block',
            blockedBy: $blockedBy,
            warnings: $warnings,
            reasonCodes: $reasonCodes === [] ? ['risk_guard_clear'] : $reasonCodes,
        );
    }
}

<?php

namespace App\Services\Risk;

class OpportunityCostService
{
    /**
     * Observe-only opportunity cost service v1.
     *
     * Supported context keys:
     * - current_trade_ev: int 0-100
     * - best_alternative_ev: int 0-100
     * - capital_locked_pct: int 0-100
     * - portfolio_heat: low|medium|high|critical
     */
    public function evaluate(array $context = []): array
    {
        $reasonCodes = [];

        $currentTradeEv = $this->score($context, 'current_trade_ev', 50);
        $bestAlternativeEv = $this->score($context, 'best_alternative_ev', 50);
        $capitalLocked = $this->score($context, 'capital_locked_pct', 0);
        $portfolioHeat = $context['portfolio_heat'] ?? 'low';

        $evGap = max(0, $bestAlternativeEv - $currentTradeEv);
        $score = (int) min(100, round(($evGap * 0.70) + ($capitalLocked * 0.30)));

        if ($evGap >= 30) {
            $reasonCodes[] = 'better_opportunity_detected';
        }

        if ($capitalLocked >= 70) {
            $reasonCodes[] = 'capital_locked_high';
        }

        if (in_array($portfolioHeat, ['high', 'critical'], true)) {
            $reasonCodes[] = 'portfolio_heat_increases_opportunity_cost';
        }

        if ($reasonCodes === []) {
            $reasonCodes[] = 'opportunity_cost_low';
        }

        return [
            'score' => $score,
            'ev_gap' => $evGap,
            'recommendation' => $this->recommendation($score),
            'reason_codes' => $reasonCodes,
        ];
    }

    private function recommendation(int $score): string
    {
        return match (true) {
            $score >= 70 => 'reallocation_watch',
            $score >= 40 => 'capital_efficiency_watch',
            default => 'hold_capital_allocation',
        };
    }

    private function score(array $context, string $key, int $default): int
    {
        return max(0, min(100, (int) ($context[$key] ?? $default)));
    }
}

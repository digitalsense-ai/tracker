<?php

namespace App\Services\Trading;

use App\DTO\AI\TradeLifecycleResult;
use App\Enums\AI\ThesisState;

class TradeLifecycleService
{
    /**
     * Foundation trade lifecycle service.
     *
     * Trades should eventually evolve through lifecycle states
     * instead of being treated as static entry/exit events.
     *
     * Initial implementation is informational and observe-only.
     */
    public function evaluate(array $context = []): TradeLifecycleResult
    {
        return new TradeLifecycleResult(
            state: ThesisState::Unknown,
            thesisStrength: 0,
            warnings: [],
            recommendations: [],
        );
    }
}

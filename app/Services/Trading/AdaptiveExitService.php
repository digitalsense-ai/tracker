<?php

namespace App\Services\Trading;

use App\Enums\AI\AiRecommendedAction;

class AdaptiveExitService
{
    /**
     * Foundation adaptive exit service.
     *
     * Future versions may recommend:
     * - hold
     * - reduce
     * - partial take profit
     * - early exit
     * - tighter trailing logic
     *
     * Initial implementation is observe-only.
     */
    public function evaluate(array $context = []): string
    {
        return AiRecommendedAction::HoldTrade->value;
    }
}

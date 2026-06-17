<?php

namespace App\Services\Governance;

class SafeModeGovernanceService
{
    /**
     * Observe-only safe mode governance service v1.
     */
    public function recommend(array $context = []): array
    {
        return [
            'safe_mode_recommended' => (bool) ($context['safe_mode_recommended'] ?? false),
            'paper_only_recommended' => (bool) ($context['paper_only_recommended'] ?? false),
            'manual_review_recommended' => (bool) ($context['manual_review_recommended'] ?? false),
            'restricted_execution_recommended' => (bool) ($context['restricted_execution_recommended'] ?? false),
            'observe_only' => true,
        ];
    }
}

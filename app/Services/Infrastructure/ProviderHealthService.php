<?php

namespace App\Services\Infrastructure;

class ProviderHealthService
{
    /**
     * Observe-only provider health service v1.
     *
     * Initial implementation is metadata/status only.
     */
    public function status(array $provider = []): array
    {
        return [
            'provider' => $provider['name'] ?? 'unknown',
            'enabled' => (bool) ($provider['enabled'] ?? true),
            'mode' => $provider['mode'] ?? 'simulation',
            'health' => $provider['health'] ?? 'unknown',
            'checked_at' => now()->toIso8601String(),
        ];
    }

    public function healthy(array $provider = []): bool
    {
        return ($provider['health'] ?? null) === 'healthy';
    }
}

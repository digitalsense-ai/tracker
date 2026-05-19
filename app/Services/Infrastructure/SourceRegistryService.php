<?php

namespace App\Services\Infrastructure;

class SourceRegistryService
{
    /**
     * Observe-only source registry service v1.
     *
     * Central access layer for configured providers.
     */
    public function all(): array
    {
        return config('trading_sources', []);
    }

    public function category(string $category): array
    {
        return config('trading_sources.'.$category, []);
    }

    public function primary(string $category): ?string
    {
        return config('trading_sources.'.$category.'.primary');
    }

    public function fallback(string $category): ?string
    {
        return config('trading_sources.'.$category.'.fallback');
    }

    public function mode(): string
    {
        return config('trading_sources.default_mode', 'simulation');
    }
}

<?php

namespace Tests\Unit;

use App\Services\Infrastructure\ProviderHealthService;
use App\Services\Infrastructure\SourceRegistryService;
use PHPUnit\Framework\TestCase;

class ProviderInfrastructureTest extends TestCase
{
    public function test_source_registry_returns_configured_mode(): void
    {
        config()->set('trading_sources.default_mode', 'paper');

        $service = new SourceRegistryService();

        $this->assertSame('paper', $service->mode());
    }

    public function test_source_registry_returns_primary_provider(): void
    {
        config()->set('trading_sources.ai.primary', 'openai');

        $service = new SourceRegistryService();

        $this->assertSame('openai', $service->primary('ai'));
    }
}

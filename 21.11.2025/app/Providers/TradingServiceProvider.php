<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PriceFeed\PriceFeedInterface;
use App\Services\PriceFeed\MockPriceFeed;

class TradingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PriceFeedInterface::class, function(){ return new MockPriceFeed(); });
    }

    public function boot(): void {}
}

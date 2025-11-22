<?php

namespace App\Services\PriceFeed;

class MockPriceFeed implements PriceFeedInterface
{
    private array $quotes;

    public function __construct()
    {
        $this->quotes = [
            'AAPL' => 225.10,
            'TSLA' => 248.35,
            'MSFT' => 412.22,
        ];
    }

    public function last(string $ticker): ?float
    {
        return $this->quotes[$ticker] ?? 100.00;
    }

    public function indicators(string $ticker): array
    {
        $p = $this->quotes[$ticker] ?? 100.00;
        return ['atr' => 2.5, 'rsi' => 55, 'vwap' => $p - 0.5];
    }
}

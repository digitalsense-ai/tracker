<?php

namespace App\Services\PriceFeed;

interface PriceFeedInterface
{
    public function last(string $ticker): ?float;
    public function indicators(string $ticker): array;
}

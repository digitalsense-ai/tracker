<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stock;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['ticker' => 'TSLA', 'gap' => 3.1, 'rvol' => 1.7, 'volume' => 2000000, 'forecast' => 'gap-up'],
            ['ticker' => 'AAPL', 'gap' => 2.8, 'rvol' => 1.4, 'volume' => 1500000, 'forecast' => 'consolidation'],
            ['ticker' => 'NVDA', 'gap' => 4.2, 'rvol' => 2.3, 'volume' => 4000000, 'forecast' => 'breakout-ready'],
        ];

        foreach ($data as $row) {
            Stock::create($row);
        }
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\YahooStockService;
use App\Models\Stock;

class FetchForecastStocks extends Command
{
    protected $signature = 'forecast:refresh';
    protected $description = 'Fetch and store forecastable stocks from Yahoo';

    public function handle()
    {
        $settings = [
            'min_gap' => 3,
            'min_rvol' => 1.5,
            'min_volume' => 2000000,
            'forecast_type' => 'gap-up',
        ];

        $stocks = YahooStockService::getLiveForecastStocks($settings);

        Stock::where('status', 'forecast')->delete();

        foreach ($stocks as $stock) {
            Stock::create([
                'ticker' => $stock->ticker,
                'gap' => $stock->gap,
                'rvol' => $stock->rvol,
                'volume' => $stock->volume,
                'status' => $stock->status,
                'forecast' => $stock->forecast,
            ]);
        }

        $this->info("Forecast stocks updated: " . count($stocks));
    }
}

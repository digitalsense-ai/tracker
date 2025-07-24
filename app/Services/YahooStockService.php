<?php

namespace App\Services;

class YahooStockService
{
    public static function getLiveForecastStocks(array $forecastSettings, string $tickerFile = 'tickers_nordnet_usa.txt'): array
    {
        $tickers = file(base_path($tickerFile), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $chunks = array_chunk($tickers, 10); // Yahoo API allows up to 10 symbols per request

        $matchedStocks = [];

        foreach ($chunks as $chunk) {
            $symbols = implode(',', $chunk);
            $url = "https://query1.finance.yahoo.com/v7/finance/quote?symbols={$symbols}";

            $response = @file_get_contents($url);
            if (!$response) continue;

            $data = json_decode($response, true);

            foreach ($data['quoteResponse']['result'] as $item) {
                $symbol = $item['symbol'];
                $prePrice = $item['preMarketPrice'] ?? null;
                $close = $item['regularMarketPreviousClose'] ?? null;
                $volume = $item['regularMarketVolume'] ?? 0;
                $avgVol = $item['averageDailyVolume3Month'] ?? 1;

                if (!$prePrice || !$close) continue;

                $gap = round((($prePrice - $close) / $close) * 100, 2);
                $rvol = $avgVol > 0 ? round($volume / $avgVol, 2) : 0;

                if (
                    $gap >= $forecastSettings['min_gap'] &&
                    $rvol >= $forecastSettings['min_rvol'] &&
                    $volume >= $forecastSettings['min_volume']
                ) {
                    $matchedStocks[] = (object)[
                        'ticker' => $symbol,
                        'gap' => $gap,
                        'rvol' => $rvol,
                        'volume' => $volume,
                        'status' => 'forecast',
                        'forecast' => $forecastSettings['forecast_type']
                    ];
                }
            }
        }

        return $matchedStocks;
    }
}

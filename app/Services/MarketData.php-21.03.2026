<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Models\SymbolMapping;
use App\Services\SaxoTokenService;

use Carbon\Carbon;

class MarketData
{
    protected SaxoTokenService $tokenService;

    public function __construct(SaxoTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Get price for a symbol
     *
     * @param string $symbol
     * @return float|null
     */
    public function getPrice(string $symbol): ?float
    {
        $map = SymbolMapping::where('symbol', strtoupper($symbol))
            ->where('enabled_for_ai', true)
            ->first();

        if (!$map) {
            return null;
        }

        try {
            // 1️⃣ Get token (throws exception if no token yet)
            $accessToken = $this->tokenService->getToken();

            // 2️⃣ Call Saxo API
            $resp = Http::withToken($accessToken)
                ->get(config('services.saxo.base_url') . '/trade/v1/infoprices', [
                    'Uic'       => $map->saxo_uic,
                    'AssetType' => $map->saxo_asset_type,
                ]);

            if (!$resp->successful()) {
                Log::channel('saxo')->error('Saxo InfoPrice request failed', [
                    'symbol' => $symbol,
                    'status' => $resp->status(),
                    'body'   => $resp->body(),
                ]);
                return null;
            }

            $data = $resp->json();
            Log::channel('saxo')->info('Saxo InfoPrice json', [
                'symbol' => $symbol,
                'json'   => $data
            ]);

            $q = $data['Quote'] ?? [];
            return $q['Mid'] ?? $q['Ask'] ?? $q['Bid'] ?? null;

        } catch (\Exception $e) {
            Log::channel('saxo')->error('MarketData getPrice error: ' . $e->getMessage(), [
                'symbol' => $symbol
            ]);
            return null;
        }
    }

    protected function getEnabledMapping(string $symbol): ?SymbolMapping
    {
        return SymbolMapping::where('symbol', strtoupper($symbol))
            ->where('enabled_for_ai', true)
            ->first();
    }

    protected function getAccessToken(): string
    {
        return $this->tokenService->getToken();
    }

    protected function getChartData(string $symbol, int $horizon, int $count = 100): array
    {
        $map = $this->getEnabledMapping($symbol);

        if (!$map) {
            return [];
        }

        try {
            $accessToken = $this->getAccessToken();

            $resp = Http::withToken($accessToken)
                ->get(config('services.saxo.base_url') . '/chart/v1/charts', [
                    'Uic'         => $map->saxo_uic,
                    'AssetType'   => $map->saxo_asset_type,
                    'Horizon'     => $horizon,
                    'Count'       => $count,
                    'Time'        => now('UTC')->toIso8601String(),
                    'FieldGroups' => 'Data',
                ]);

            if (!$resp->successful()) {
                Log::channel('saxo')->warning('Chart request failed', [
                    'symbol' => $symbol,
                    'status' => $resp->status(),
                    'body'   => $resp->body(),
                    'horizon'=> $horizon,
                    'count'  => $count,
                ]);
                return [];
            }

            return $resp->json()['Data'] ?? [];
        } catch (\Throwable $e) {
            Log::channel('saxo')->error('getChartData error: ' . $e->getMessage(), [
                'symbol' => $symbol,
                'horizon'=> $horizon,
                'count'  => $count,
            ]);
            return [];
        }
    }

    public function getPreviousClose(string $symbol): ?float
    {
        $bars = $this->getChartData($symbol, 1440, 2);

        if (count($bars) < 2) {
            return null;
        }

        $previousBar = $bars[count($bars) - 2];

        return isset($previousBar['Close']) ? (float) $previousBar['Close'] : null;
    }

    public function getIntradayVWAP(string $symbol): ?float
    {
        $bars = $this->getChartData($symbol, 5, 100);

        if (empty($bars)) {
            return null;
        }

        $pvSum = 0.0;
        $vSum = 0.0;

        foreach ($bars as $bar) {
            $high = (float) ($bar['High'] ?? 0);
            $low = (float) ($bar['Low'] ?? 0);
            $close = (float) ($bar['Close'] ?? 0);
            $volume = (float) ($bar['Volume'] ?? 0);

            if ($volume <= 0) {
                continue;
            }

            $typicalPrice = ($high + $low + $close) / 3;
            $pvSum += $typicalPrice * $volume;
            $vSum += $volume;
        }

        if ($vSum <= 0) {
            return null;
        }

        return round($pvSum / $vSum, 4);
    }

    public function getAverageVolume(string $symbol, int $count = 20): ?float
    {
        $bars = $this->getChartData($symbol, 1440, $count);

        if (empty($bars)) {
            return null;
        }

        $volumes = collect($bars)
            ->pluck('Volume')
            ->filter(fn ($v) => $v !== null)
            ->map(fn ($v) => (float) $v)
            ->values();

        if ($volumes->isEmpty()) {
            return null;
        }

        return round($volumes->avg(), 2);
    }

    public function getCurrentVolume(string $symbol): ?float
    {
        $map = $this->getEnabledMapping($symbol);

        if (!$map) {
            return null;
        }

        try {
            $accessToken = $this->getAccessToken();

            $resp = Http::withToken($accessToken)
                ->get(config('services.saxo.base_url') . '/trade/v1/infoprices', [
                    'Uic'       => $map->saxo_uic,
                    'AssetType' => $map->saxo_asset_type,
                ]);

            if (!$resp->successful()) {
                Log::channel('saxo')->warning('getCurrentVolume failed', [
                    'symbol' => $symbol,
                    'status' => $resp->status(),
                    'body'   => $resp->body(),
                ]);
                return null;
            }

            $data = $resp->json();
            $quote = $data['Quote'] ?? [];

            return isset($quote['Volume']) ? (float) $quote['Volume'] : null;
        } catch (\Throwable $e) {
            Log::channel('saxo')->error('getCurrentVolume error: ' . $e->getMessage(), [
                'symbol' => $symbol,
            ]);
            return null;
        }
    }

    public function getIntradayHigh(string $symbol): ?float
    {
        $bars = $this->getChartData($symbol, 5, 100);

        if (empty($bars)) {
            return null;
        }

        $highs = collect($bars)
            ->pluck('High')
            ->filter(fn ($v) => $v !== null)
            ->map(fn ($v) => (float) $v);

        return $highs->isEmpty() ? null : $highs->max();
    }

    public function getIntradayLow(string $symbol): ?float
    {
        $bars = $this->getChartData($symbol, 5, 100);

        if (empty($bars)) {
            return null;
        }

        $lows = collect($bars)
            ->pluck('Low')
            ->filter(fn ($v) => $v !== null)
            ->map(fn ($v) => (float) $v);

        return $lows->isEmpty() ? null : $lows->min();
    }

    //     /*
//    public function getPrice(string $symbol): float
//     {
//         $apiKey = config('services.finnhub.key');
//         if (!$apiKey) {
//             throw new \RuntimeException('FINNHUB_API_KEY missing from .env');
//         }

//         if (str_starts_with($symbol, 'xyz:')) {
//             $symbol = substr($symbol, 4);
//         }

//         $dkMapping = [
//             'NOVO-B' => 'NOVO-B.CO',
//             'DSV'    => 'DSV.CO',
//             'ORSTED' => 'ORSTED.CO',
//             'VWS'    => 'VWS.CO',
//             'GEN'    => 'GEN.CO',
//             'NZYM-B' => 'NZYM-B.CO',
//         ];

//         $extSymbol = $dkMapping[$symbol] ?? $symbol;

//         $response = Http::timeout(5)->get('https://finnhub.io/api/v1/quote', [
//             'symbol' => $extSymbol,
//             'token'  => $apiKey,
//         ]);

//         if (!$response->successful()) {
//             \Log::warning('Finnhub request failed', [
//                 'status' => $response->status(),
//                 'body'   => $response->body(),
//                 'symbol' => $extSymbol,
//             ]);
//             return 0.0;
//         }

//         $data = $response->json();
//         $price = $data['c'] ?? null;

//         if ($price === null) {
//             \Log::warning('Finnhub returned no price', [
//                 'symbol' => $extSymbol,
//                 'data'   => $data,
//             ]);
//             return 0.0;
//         }

//         return (float) $price;
//     }
//     */
}
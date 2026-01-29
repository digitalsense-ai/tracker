<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use App\Models\SymbolMapping;
use App\Services\SaxoTokenService;

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
<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

use App\Models\SymbolMapping;

class MarketData
{
    public function getPrice(string $symbol): ?float {
        $map = SymbolMapping::where('symbol', strtoupper($symbol))->where('enabled_for_ai',true)->first();

        if(!$map) return null;

        $resp = Http::withToken(config('services.saxo.access_token'))
            ->get(config('services.saxo.base_url').'/trade/v1/infoprices',[
            'Uic'=>$map->saxo_uic,
            'AssetType'=>$map->saxo_asset_type,
            ]);

        if(!$resp->successful()) return null;

        $q = $resp->json()['Quote'] ?? [];

        return $q['Mid'] ?? $q['Ask'] ?? $q['Bid'] ?? null;
    }
    /*
   public function getPrice(string $symbol): float
    {
        $apiKey = config('services.finnhub.key');
        if (!$apiKey) {
            throw new \RuntimeException('FINNHUB_API_KEY missing from .env');
        }

        if (str_starts_with($symbol, 'xyz:')) {
            $symbol = substr($symbol, 4);
        }

        $dkMapping = [
            'NOVO-B' => 'NOVO-B.CO',
            'DSV'    => 'DSV.CO',
            'ORSTED' => 'ORSTED.CO',
            'VWS'    => 'VWS.CO',
            'GEN'    => 'GEN.CO',
            'NZYM-B' => 'NZYM-B.CO',
        ];

        $extSymbol = $dkMapping[$symbol] ?? $symbol;

        $response = Http::timeout(5)->get('https://finnhub.io/api/v1/quote', [
            'symbol' => $extSymbol,
            'token'  => $apiKey,
        ]);

        if (!$response->successful()) {
            \Log::warning('Finnhub request failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
                'symbol' => $extSymbol,
            ]);
            return 0.0;
        }

        $data = $response->json();
        $price = $data['c'] ?? null;

        if ($price === null) {
            \Log::warning('Finnhub returned no price', [
                'symbol' => $extSymbol,
                'data'   => $data,
            ]);
            return 0.0;
        }

        return (float) $price;
    }
    */
}
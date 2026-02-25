<?php 

use Illuminate\Support\Facades\Http;

class SaxoInstrumentService
{
    public function getAllSymbols(): array
    {
        $accessToken = app(SaxoTokenService::class)->getToken();

        $response = Http::withToken($accessToken)
            ->get('https://gateway.saxobank.com/sim/openapi/ref/v1/instruments');

        if ($response->failed()) {
            throw new \Exception('Failed to fetch instruments: ' . $response->body());
        }

        $data = $response->json();

        // Extract symbols
        $symbols = collect($data['Data'] ?? [])
            ->pluck('Symbol')
            ->unique()
            ->values()
            ->all();

        return $symbols;
    }
}

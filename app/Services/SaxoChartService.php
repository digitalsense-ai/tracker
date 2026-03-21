<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SaxoChartService
{
    protected SaxoTokenService $tokenService;

    public function __construct(SaxoTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Get daily OHLC bars from Saxo
     *
     * @param int    $uic
     * @param string $assetType
     * @param int    $count
     * @return array
     */
    public function getDailyBars(int $uic, string $assetType, int $count = 30): array
    {
        try {
            $token = $this->tokenService->getToken();

            // $resp = Http::withToken($token)
            //     ->get(config('services.saxo.base_url') . '/chart/v1/charts', [
            //         'Uic'       => $uic,
            //         'AssetType' => $assetType,
            //         'Horizon'   => 1440,
            //         'Count'     => $count,
            //         'Mode'      => 'UpTo',
            //     ]);


            $resp = Http::withToken($token)
                     ->get(rtrim(config('services.saxo.base_url'), '/') . '/chart/v3/charts', [
                         'Uic'       => $uic,
                         'AssetType' => $assetType, // "Stock"
                         'Horizon'   => 1440,       // daily - 24hours * 60mins
                         'Count'     => $count,     // <= 1200
                         'FieldGroups' => 'Data',   // optional (default is [Data])
                     ]);

            Log::channel('saxo')->info('Saxo chart response', [
             'uic' => $uic,
             'status' => $resp->status(),
             'body' => $resp->body(),
            ]);

            if (!$resp->successful()) {
                Log::channel('saxo')->error('Saxo chart request failed', [
                    'uic'    => $uic,
                    'status' => $resp->status(),
                    'body'   => $resp->body(),
                ]);
                return [];
            }

            $data = $resp->json();

            if (empty($data['Data'])) {
                return [];
            }

            // Normalize bars
            return collect($data['Data'])->map(function ($bar) {
                return [
                    'date'  => $bar['Time'],
                    'open'  => $bar['Open'],
                    'high'  => $bar['High'],
                    'low'   => $bar['Low'],
                    'close' => $bar['Close'],
                ];
            })->values()->all();

        } catch (\Throwable $e) {
            Log::channel('saxo')->error('Saxo chart exception', [
                'uic'   => $uic,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}

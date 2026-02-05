<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SaxoInstrument;
use Carbon\Carbon;

class SaxoSyncInstruments extends Command
{
    protected $signature = 'saxo:sync-instruments
        {--asset_types=Stock : Comma-separated AssetTypes (default: Stock)}
        {--exchange_ids=NASDAQ,NYSE : Comma-separated ExchangeIds (default: NASDAQ,NYSE)}
        {--top=200 : Page size ($top)}
        {--max=2000 : Max rows to fetch total (safety cap)}
        {--include_non_tradable=0 : IncludeNonTradable (0/1)}
    ';

    protected $description = 'Sync Saxo instruments into local DB (saxo_instruments) using ref/v1/instruments';

    public function handle(): int
    {
        $baseUrl = rtrim(config('services.saxo.base_url'), '/');
        //$token   = config('services.saxo.access_token');
        $tokenService = app(\App\Services\SaxoTokenService::class);
        $token = $tokenService->getToken();

        if (!$baseUrl || !$token) {
            $this->error('Missing Saxo base_url or access_token in config/services.php (services.saxo.*).');
            return self::FAILURE;
        }

        $assetTypes = array_values(array_filter(array_map('trim', explode(',', (string)$this->option('asset_types')))));
        $exchangeIds= array_values(array_filter(array_map('trim', explode(',', (string)$this->option('exchange_ids')))));

        $top        = max(1, (int)$this->option('top'));
        $max        = max(1, (int)$this->option('max'));
        $includeNon = (string)$this->option('include_non_tradable') === '1' ? 'true' : 'false';

        $fetched = 0;
        $skip    = 0;

        foreach ($exchangeIds as $exchangeId) {
            foreach ($assetTypes as $assetType) {
                $skip = 0;

                while ($fetched < $max) {
                    $url = $baseUrl . '/ref/v1/instruments';

                    $query = [
                        'AssetTypes'          => $assetType,
                        'ExchangeId'          => $exchangeId,
                        'IncludeNonTradable'  => $includeNon,
                        '$top'                => $top,
                        '$skip'               => $skip,
                    ];

                    $resp = Http::withToken($token)
                        ->timeout(30)
                        ->get($url, $query);

                    if (!$resp->successful()) {
                        Log::warning('Saxo instruments sync failed', [
                            'status' => $resp->status(),
                            'body'   => $resp->body(),
                            'query'  => $query,
                        ]);
                        $this->error("Saxo instruments fetch failed: {$resp->status()}");
                        return self::FAILURE;
                    }

                    $payload = $resp->json();
                    $data = $payload['Data'] ?? [];
                    if (!is_array($data) || count($data) === 0) {
                        break; // done for this exchange/assetType
                    }

                    $now = Carbon::now();

                    foreach ($data as $row) {
                        // Expected fields from Saxo:
                        // Identifier (UIC), AssetType, Symbol, Description, ExchangeId, CurrencyCode, TradableAs
                        $uic = $row['Identifier'] ?? null;
                        $sym = $row['Symbol'] ?? null;

                        if (!$uic || !$sym) {
                            continue;
                        }

                        SaxoInstrument::updateOrCreate(
                            [
                                'asset_type' => $row['AssetType'] ?? $assetType,
                                'uic'        => (int)$uic,
                            ],
                            [
                                'symbol'        => $sym,
                                'description'   => $row['Description'] ?? null,
                                'exchange_id'   => $row['ExchangeId'] ?? $exchangeId,
                                'currency_code' => $row['CurrencyCode'] ?? null,
                                'tradable_as'   => $row['TradableAs'] ?? null,
                                'is_tradable'   => true,
                                'raw'           => $row,
                                'last_seen_at'  => $now,
                            ]
                        );

                        $fetched++;
                        if ($fetched >= $max) {
                            break 3;
                        }
                    }

                    $skip += $top;
                    $this->info("Synced {$fetched} instruments so far... (exchange={$exchangeId}, asset={$assetType}, skip={$skip})");
                }
            }
        }

        $this->info("Done. Total instruments synced: {$fetched}");
        return self::SUCCESS;
    }
}

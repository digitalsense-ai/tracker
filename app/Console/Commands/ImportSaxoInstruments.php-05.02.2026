<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\SaxoTokenService;
use App\Models\SaxoInstrument;

class ImportSaxoInstruments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */    
    protected $signature = 'saxo:import-instruments';
    /**
     * The console command description.
     *
     * @var string
     */    
    protected $description = 'Import instruments from Saxo ref/v1/instruments';
    /**
     * Execute the console command.
     */
    public function handle(SaxoTokenService $tokenService)
    {
        $this->info('Starting Saxo instruments import...');

        $accessToken = $tokenService->getToken();
        $skip = 0;
        $top = 100;
        $totalImported = 0;

        do {
            $response = Http::withToken($accessToken)
                ->get('https://gateway.saxobank.com/sim/openapi/ref/v1/instruments', [
                    'AssetTypes' => 'Stock',
                    //'Class' => 'Equity',
                    'IncludeNonTradable' => 'false',
                    '$top' => $top,
                    '$skip' => $skip
                ]);
            
            if ($response->status() == 429) {  // 429 = Too Many Requests
                $this->warn("Rate limit hit, waiting 5 seconds...");
                sleep(5);                     // pause
                continue;                      // retry the same page
            }

            if ($response->failed()) {
                $this->error('Failed to fetch instruments: ' . $response->body());
                return 1;
            }

            $data = $response->json()['Data'] ?? [];
            if (empty($data)) {
                break;
            }

            foreach ($data as $instrument) {
                SaxoInstrument::updateOrCreate(
                    ['uic' => $instrument['Identifier']],
                    [
                        'symbol' => $instrument['Symbol'],
                        'description' => $instrument['Description'] ?? '',
                        'asset_type' => $instrument['AssetType'] ?? '',
                        'exchange_id' => $instrument['ExchangeId'] ?? '',
                        'is_tradable' => !empty($instrument['TradableAs']),
                        'currency' => $instrument['CurrencyCode'] ?? null,
                        'raw_json' => json_encode($instrument),
                    ]
                );
                $totalImported++;
            }

            $skip += $top;

            // ⏱ Delay to avoid rate limit
            sleep(1); // 1 second between pages
        } while (count($data) === $top);

        $this->info("Imported/updated $totalImported instruments.");
        return 0;
    }
}

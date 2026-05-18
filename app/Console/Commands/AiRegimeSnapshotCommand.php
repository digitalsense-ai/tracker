<?php

namespace App\Console\Commands;

use App\Models\AiDecisionLog;
use App\Models\AiRegimeSnapshot;
use App\Services\AI\MarketRegimeService;
use Illuminate\Console\Command;

class AiRegimeSnapshotCommand extends Command
{
    protected $signature = 'ai:regime-snapshot {--symbol=}';

    protected $description = 'Detect and store AI market regime snapshots in observe-only mode.';

    public function handle(MarketRegimeService $service): int
    {
        $symbol = $this->option('symbol');

        $result = $service->detect([
            'symbol' => $symbol,
        ]);

        AiRegimeSnapshot::create([
            'symbol' => $symbol,
            'primary_regime' => $result->primaryRegime->value,
            'confidence' => $result->confidence,
            'secondary_regimes' => $result->secondaryRegimes,
            'reason_codes' => $result->reasonCodes,
            'payload' => [],
        ]);

        AiDecisionLog::create([
            'module' => 'market_regime',
            'activation_mode' => 'observe_only',
            'recommended_action' => 'continue',
            'confidence' => $result->confidence,
            'reason_codes' => $result->reasonCodes,
            'output_payload' => [
                'symbol' => $symbol,
                'primary_regime' => $result->primaryRegime->value,
                'secondary_regimes' => $result->secondaryRegimes,
            ],
            'action_executed' => false,
        ]);

        $this->info('AI regime snapshot stored in observe-only mode.');

        return self::SUCCESS;
    }
}

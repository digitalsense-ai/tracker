<?php

namespace App\Console\Commands;

use App\Models\AiConfidenceSnapshot;
use App\Models\AiDecisionLog;
use App\Services\AI\ConfidenceEngineService;
use Illuminate\Console\Command;

class AiConfidenceSnapshotCommand extends Command
{
    protected $signature = 'ai:confidence-snapshot {--subject_type=} {--subject_id=}';

    protected $description = 'Calculate and store AI confidence snapshots in observe-only mode.';

    public function handle(ConfidenceEngineService $service): int
    {
        $result = $service->calculate();

        $subjectType = $this->option('subject_type');
        $subjectId = $this->option('subject_id');

        AiConfidenceSnapshot::create([
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'confidence' => $result->confidence,
            'uncertainty' => $result->uncertainty,
            'level' => $result->level,
            'reason_codes' => $result->reasonCodes,
            'payload' => [],
        ]);

        AiDecisionLog::create([
            'module' => 'confidence_engine',
            'activation_mode' => 'observe_only',
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'confidence' => $result->confidence,
            'uncertainty' => $result->uncertainty,
            'reason_codes' => $result->reasonCodes,
            'output_payload' => [
                'level' => $result->level,
            ],
            'action_executed' => false,
        ]);

        $this->info('AI confidence snapshot stored in observe-only mode.');

        return self::SUCCESS;
    }
}

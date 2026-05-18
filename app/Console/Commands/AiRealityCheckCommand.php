<?php

namespace App\Console\Commands;

use App\Models\AiDecisionLog;
use App\Models\AiRealityCheck;
use App\Services\AI\AiRealityCheckService;
use Illuminate\Console\Command;

class AiRealityCheckCommand extends Command
{
    protected $signature = 'ai:reality-check {--subject_type=} {--subject_id=}';

    protected $description = 'Run the AI reality check in observe-only mode.';

    public function handle(AiRealityCheckService $service): int
    {
        $result = $service->evaluate([
            'subject_type' => $this->option('subject_type'),
            'subject_id' => $this->option('subject_id'),
        ]);

        $subjectType = $this->option('subject_type');
        $subjectId = $this->option('subject_id');

        AiRealityCheck::create([
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'plan_still_valid' => $result->planStillValid,
            'setup_still_valid' => $result->setupStillValid,
            'regime_changed' => $result->regimeChanged,
            'news_risk_changed' => $result->newsRiskChanged,
            'recommended_action' => $result->recommendedAction,
            'reason_codes' => $result->reasonCodes,
            'payload' => [],
        ]);

        AiDecisionLog::create([
            'module' => 'reality_check',
            'activation_mode' => 'observe_only',
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'recommended_action' => $result->recommendedAction,
            'reason_codes' => $result->reasonCodes,
            'output_payload' => [
                'plan_still_valid' => $result->planStillValid,
                'setup_still_valid' => $result->setupStillValid,
                'regime_changed' => $result->regimeChanged,
                'news_risk_changed' => $result->newsRiskChanged,
            ],
            'action_executed' => false,
        ]);

        $this->info('AI reality check logged in observe-only mode.');

        return self::SUCCESS;
    }
}

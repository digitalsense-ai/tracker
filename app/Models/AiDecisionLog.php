<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiDecisionLog extends Model
{
    protected $fillable = [
        'module',
        'activation_mode',
        'subject_type',
        'subject_id',
        'recommended_action',
        'confidence',
        'uncertainty',
        'reason_codes',
        'input_summary',
        'output_payload',
        'action_executed',
    ];

    protected $casts = [
        'reason_codes' => 'array',
        'input_summary' => 'array',
        'output_payload' => 'array',
        'action_executed' => 'boolean',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}

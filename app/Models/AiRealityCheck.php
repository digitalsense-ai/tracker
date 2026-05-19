<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiRealityCheck extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'plan_still_valid',
        'setup_still_valid',
        'regime_changed',
        'news_risk_changed',
        'recommended_action',
        'reason_codes',
        'payload',
    ];

    protected $casts = [
        'plan_still_valid' => 'boolean',
        'setup_still_valid' => 'boolean',
        'regime_changed' => 'boolean',
        'news_risk_changed' => 'boolean',
        'reason_codes' => 'array',
        'payload' => 'array',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}

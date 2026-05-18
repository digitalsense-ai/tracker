<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AiConfidenceSnapshot extends Model
{
    protected $fillable = [
        'subject_type',
        'subject_id',
        'confidence',
        'uncertainty',
        'level',
        'reason_codes',
        'payload',
    ];

    protected $casts = [
        'reason_codes' => 'array',
        'payload' => 'array',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OperationalEvent extends Model
{
    use HasFactory;

    protected $table = 'system_events';

    protected $fillable = [
        'uuid',
        'event_type',
        'category',
        'severity',
        'source',
        'status',
        'correlation_id',
        'timeline_id',
        'subject_type',
        'subject_id',
        'metadata',
        'explainability_refs',
        'operator_visibility',
        'occurred_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'explainability_refs' => 'array',
        'operator_visibility' => 'boolean',
        'occurred_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}

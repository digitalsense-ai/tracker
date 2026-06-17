<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Timeline extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'timeline_type',
        'title',
        'correlation_id',
        'subject_type',
        'subject_id',
        'status',
        'metadata',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}

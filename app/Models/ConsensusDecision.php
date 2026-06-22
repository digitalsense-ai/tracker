<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsensusDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid','correlation_id','timeline_id','decision_type','recommendation',
        'agreement_score','conflict_score','confidence','conflict_level',
        'model_votes','weights','reason_codes','metadata','observe_only'
    ];

    protected $casts = [
        'model_votes' => 'array',
        'weights' => 'array',
        'reason_codes' => 'array',
        'metadata' => 'array',
        'observe_only' => 'boolean',
    ];
}

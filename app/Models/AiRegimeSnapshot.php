<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiRegimeSnapshot extends Model
{
    protected $fillable = [
        'symbol',
        'primary_regime',
        'confidence',
        'secondary_regimes',
        'reason_codes',
        'payload',
    ];

    protected $casts = [
        'secondary_regimes' => 'array',
        'reason_codes' => 'array',
        'payload' => 'array',
    ];
}

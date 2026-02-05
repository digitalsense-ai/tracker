<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiDailyCandidate extends Model
{
    protected $table = 'ai_daily_candidates';

    protected $fillable = [
        'ai_model_id',
        'trade_date',
        'symbols_json',
        'meta_json',
    ];

    protected $casts = [
        'trade_date'  => 'date',
        'symbols_json'=> 'array',
        'meta_json'   => 'array',
    ];
}

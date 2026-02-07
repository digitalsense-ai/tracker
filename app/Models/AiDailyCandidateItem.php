<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiDailyCandidateItem extends Model
{
    protected $table = 'ai_daily_candidate_items';

    protected $fillable = [
        'ai_model_id',
        'trade_date',
        'symbol',
        'rank',
        'score',
        'price',
        'saxo_uic',
        'saxo_asset_type',
        'metrics_json',
        'source',
    ];

    protected $casts = [
        'trade_date'   => 'date',
        'metrics_json' => 'array',
        'score'        => 'float',
        'price'        => 'float',
        'rank'         => 'integer',
        'saxo_uic'     => 'integer',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiDailyPlan extends Model
{
    protected $fillable = [
        'ai_model_id',
        'trade_date',
        'plan_json',
    ];

    protected $casts = [
        'trade_date' => 'date',
        'plan_json'  => 'array',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }
}

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
        'locked_start_prompt',
        'locked_loop_prompt',
        'locked_premarket_prompt',
    ];

    protected $casts = [
        'plan_json'  => 'array',
        'trade_date' => 'date',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }
}

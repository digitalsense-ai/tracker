<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Position extends Model
{
    protected $fillable = [
        'ai_model_id','ticker','side','qty','avg_price','stop_price','target_price',
        'leverage','margin','unrealized_pnl','status','opened_at'
    ];

    protected $casts = [
        'opened_at' => 'datetime', 'qty' => 'float', 'avg_price' => 'float',
        'stop_price' => 'float', 'target_price' => 'float',
        'leverage' => 'float', 'margin' => 'float', 'unrealized_pnl' => 'float',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }
}

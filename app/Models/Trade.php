<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    protected $fillable = [
        'ai_model_id','ticker','side','entry_price','exit_price','qty',
        'holding_seconds','notional_entry','notional_exit','fees','net_pnl','opened_at','closed_at'
    ];

    protected $casts = [
        'qty' => 'float', 'entry_price' => 'float', 'exit_price' => 'float',
        'notional_entry' => 'float', 'notional_exit' => 'float',
        'fees' => 'float', 'net_pnl' => 'float',
        'opened_at' => 'datetime', 'closed_at' => 'datetime',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquitySnapshot extends Model
{
    protected $fillable = [
        'ai_model_id',
        'equity',
        'taken_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'equity'   => 'float',
    ];

    public function model()
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }
}

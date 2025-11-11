<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    protected $fillable = [
        'name','slug','wallet','equity','return_pct','active',
        'start_prompt','loop_prompt','check_interval_min','tags'
    ];

    protected $casts = [
        'active' => 'bool',
        'tags'   => 'array',
    ];
}

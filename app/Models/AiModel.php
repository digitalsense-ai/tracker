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

    public function positions()
	{
	   return $this->hasMany(\App\Models\Position::class, 'ai_model_id');
	}

	public function trades()
	{
	   return $this->hasMany(\App\Models\Trade::class, 'ai_model_id');
	}
	
	public function equitySnapshots()
	{
	   return $this->hasMany(\App\Models\EquitySnapshot::class, 'ai_model_id');
	}
}

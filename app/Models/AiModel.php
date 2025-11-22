<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    protected $fillable = [
        'name', 'slug', 'wallet', 'equity', 'return_pct', 'active',
        'start_prompt', 'loop_prompt', 'premarket_prompt',
        'check_interval_min', 'premarket_run_time',
        'max_strategies_per_day', 'max_symbols_per_day',
        'allow_sleeper_strategies', 'default_risk_per_strategy_pct',
        'allow_activate_sleepers', 'allow_early_exit_on_invalidation',
        'max_adds_per_position', 'loop_min_price_move_pct',
        'tags',
    ];      

    protected $casts = [
        'active'                         => 'bool',
        'tags'                           => 'array',
        'allow_sleeper_strategies'       => 'bool',
        'allow_activate_sleepers'        => 'bool',
        'allow_early_exit_on_invalidation' => 'bool',
        'max_strategies_per_day'         => 'integer',
        'max_symbols_per_day'            => 'integer',
        'max_adds_per_position'          => 'integer',
        'default_risk_per_strategy_pct'  => 'float',
        'loop_min_price_move_pct'        => 'float',
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
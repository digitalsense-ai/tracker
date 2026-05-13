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
        'tags', 'min_entry_score', 'min_hold_score',
        'take_profit_enabled', 'tp_model', 'tp1_close_pct',
        'move_sl_to_break_even_on_tp1', 'runner_trailing_enabled',
        'runner_trail_distance_rr'
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
        'min_entry_score'                => 'integer',
        'min_hold_score'                 => 'integer',
        'take_profit_enabled'            => 'bool',
        'tp1_close_pct'                  => 'float',
        'move_sl_to_break_even_on_tp1'   => 'bool',
        'runner_trailing_enabled'        => 'bool',
        'runner_trail_distance_rr'       => 'float',
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

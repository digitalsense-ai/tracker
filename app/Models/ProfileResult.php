<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileResult extends Model {
    protected $fillable=['strategy_profile_id','window','trades','winrate','avg_r','net_pl','profit_factor','drawdown_pct','score','metrics'];
    protected $casts=['metrics'=>'array','winrate'=>'float','avg_r'=>'float','net_pl'=>'float','profit_factor'=>'float','drawdown_pct'=>'float','score'=>'float'];
    public function profile(): BelongsTo {
        return $this->belongsTo(StrategyProfile::class,'strategy_profile_id');
    }
}
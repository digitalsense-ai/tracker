<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StrategyProfile extends Model {
    protected $fillable=['name','description','settings','enabled','rank'];
    protected $casts=['settings'=>'array','enabled'=>'boolean','rank'=>'float'];
    public function results(): HasMany {
        return $this->hasMany(ProfileResult::class);
    }
}
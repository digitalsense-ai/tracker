<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GovernanceEvent extends Model
{
 protected $fillable=['uuid','correlation_id','timeline_id','event_type','severity','status','recommended_action','recommendations','reason_codes','metadata','operator_visibility','observe_only'];
 protected $casts=['recommendations'=>'array','reason_codes'=>'array','metadata'=>'array','operator_visibility'=>'boolean','observe_only'=>'boolean'];
}

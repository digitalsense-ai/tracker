<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SymbolMapping extends Model {
 protected $fillable=['symbol','saxo_uic','saxo_asset_type','enabled_for_ai','priority'];
}

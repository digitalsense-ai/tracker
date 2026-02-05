<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SymbolMapping extends Model
{
    protected $table = 'symbol_mappings';

    protected $fillable = [
        'symbol',
        'saxo_uic',
        'saxo_asset_type',
        'enabled_for_ai',
        'priority',
    ];

    protected $casts = [
        'saxo_uic'       => 'integer',
        'enabled_for_ai' => 'boolean',
        'priority'       => 'integer',
    ];
}

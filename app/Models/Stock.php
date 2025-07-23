<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = [
        'ticker', 'gap', 'rvol', 'volume', 'forecast', 'status',
        'entry_price', 'sl', 'tp1', 'tp2', 'tp3'
    ];
}

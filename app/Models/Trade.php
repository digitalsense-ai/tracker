<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticker',
        'date',
        'entry_price',
        'exit_price',
        'stop_loss',
        'result',
        'forecast_type',
    ];
}

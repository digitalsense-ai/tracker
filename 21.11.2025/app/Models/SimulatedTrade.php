<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SimulatedTrade extends Model
{
    use HasFactory;

    protected $table = 'simulated_trades';

    protected $fillable = [
        'ticker',
        'date',
        'entry_price',
        'exit_price',
        'sl_price',
        'tp1',
        'tp2',
        'status',
        'fees',
        'net_profit',
        'forecast_type',
        'forecast_score',
        'trend_rating',
        'executed_on_nordnet',
    ];

    protected $casts = [
        'date' => 'date',
        'executed_on_nordnet' => 'boolean',
    ];
}

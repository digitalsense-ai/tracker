<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key','group','type','value','label','meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public static function byKey(string $key, $default = null)
    {
        $row = static::query()->where('key', $key)->first();
        return $row ? $row->value : $default;
    }
}

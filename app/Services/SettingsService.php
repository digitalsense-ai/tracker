<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    public static function get(string $key, $default = null)
    {
        $row = Setting::where('key', $key)->first();
        return $row ? $row->value : $default;
    }

    public static function set(string $key, $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function all(): array
    {
        return Setting::pluck('value', 'key')->toArray();
    }
}

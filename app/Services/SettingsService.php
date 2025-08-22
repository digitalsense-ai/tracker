<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public static function get(string $key, $default = null) {
        $keyNorm = strtoupper($key);
        return Cache::remember("setting:$keyNorm", 60, function() use ($keyNorm, $default) {
            $row = Setting::whereRaw('UPPER(`key`) = ?', [$keyNorm])->first();
            $raw = $row?->value ?? $default;
            if (is_string($raw)) {
                $lower = strtolower(trim($raw));
                if ($lower === 'true') return true;
                if ($lower === 'false') return false;
            }
            if (is_numeric($raw)) return $raw + 0;
            return $raw;
        });
    }

    public static function set(string $key, $value): void {
        $keyNorm = strtoupper($key);
        Cache::forget("setting:$keyNorm");
        Setting::updateOrCreate(['key' => $keyNorm], ['value' => (string)$value]);
    }

    public static function all(array $keys = null): array {
        if ($keys === null) {
            return Setting::all()->pluck('value','key')->toArray();
        }
        $out = [];
        foreach ($keys as $k) $out[$k] = self::get($k);
        return $out;
    }
}

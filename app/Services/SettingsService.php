<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    public function get(string $key, $default = null)
    {
        $row = Setting::where('key', $key)->first();
        if (!$row) return $default;
        return $this->cast($row->value, $row->type);
    }

    public function getMany(array $keysWithDefault): array
    {
        $out = [];
        foreach ($keysWithDefault as $key => $default) {
            $out[$key] = $this->get($key, $default);
        }
        return $out;
    }

    public function set(string $key, $value, string $type = 'string', string $group = null, string $label = null, array $meta = null): void
    {
        $row = Setting::firstOrNew(['key' => $key]);
        $row->group = $group ?? $row->group;
        $row->type  = $type;
        $row->label = $label ?? $row->label;
        $row->value = $this->uncast($value, $type);
        $row->meta  = $meta;
        $row->save();
    }

    public function allByGroups(): array
    {
        $rows = Setting::orderBy('group')->orderBy('key')->get();
        $out = [];
        foreach ($rows as $r) {
            $out[$r->group ?? 'other'][] = $r;
        }
        return $out;
    }

    protected function cast(?string $value, string $type)
    {
        if ($value === null) return null;
        return match($type) {
            'bool'  => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int'   => (int) $value,
            'float' => (float) $value,
            'time'  => $value, // 'HH:MM'
            'json'  => json_decode($value, true),
            default => $value,
        };
    }

    protected function uncast($value, string $type): string
    {
        return match($type) {
            'bool'  => $value ? '1' : '0',
            'int'   => (string) (int) $value,
            'float' => (string) (float) $value,
            'time'  => (string) $value,
            'json'  => json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };
    }
}

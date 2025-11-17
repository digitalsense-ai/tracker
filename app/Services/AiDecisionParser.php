<?php

namespace App\Services;

use Illuminate\Support\Arr;

class AiDecisionParser
{
    public static function parse(string $raw): array
    {
        $raw = trim($raw);

        if (str_starts_with($raw, '```')) {
            $raw = preg_replace('/^```[a-zA-Z]*\n?/m', '', $raw);
            $raw = preg_replace('/```\s*$/m', '', $raw);
            $raw = trim($raw);
        }

        $data = json_decode($raw, true);

        if (! is_array($data)) {
            return [
                'action'    => 'HOLD',
                'strategy'  => null,
                'reasoning' => $raw,
                'orders'    => [],
                'raw_json'  => $raw,
            ];
        }

        $action = strtoupper((string) Arr::get($data, 'action', 'HOLD'));
        if (! in_array($action, ['HOLD', 'OPEN', 'CLOSE'])) {
            $action = 'HOLD';
        }

        $orders = Arr::get($data, 'orders', []);
        if (! is_array($orders)) {
            $orders = [];
        }

        return [
            'action'    => $action,
            'strategy'  => Arr::get($data, 'strategy'),
            'reasoning' => Arr::get($data, 'reasoning'),
            'orders'    => $orders,
            'raw_json'  => json_encode($data),
        ];
    }
}

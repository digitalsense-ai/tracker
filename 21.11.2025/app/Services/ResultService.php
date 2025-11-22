<?php

namespace App\Services;

use Carbon\Carbon;

class ResultService
{
    public static function cards($filters)
    {
        // Dummy structure for illustration
        return [
            'closed' => 0,
            'winRate' => 0.0,
            'avgR' => 0.0,
            'fees' => 0.0,
            'net' => 0.0,
            'currency' => config('settings.currency', 'USD'),
        ];
    }

    public static function table($filters)
    {
        return [
            'rows' => []
        ];
    }
}

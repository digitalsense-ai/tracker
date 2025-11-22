<?php

namespace App\Services;

use Carbon\Carbon;

class KpiService
{
    public static function defaultWindow()
    {
        $end = Carbon::now();
        $start = $end->copy()->subDays(7);
        return [$start, $end];
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SettingsService;

class SettingsController extends Controller
{
    private array $keys = [
        'CURRENCY',
        'POSITION_SIZE',
        'FEE_PERCENT',
        'FEE_MIN',
        'STRATEGY_RANGE_MINUTES',
        'STRATEGY_REQUIRE_RETEST',
        'ENTRY_BUFFER_PERCENT',
        'SL_BUFFER_PERCENT',
        'SESSION_START',
        'SESSION_END',
        'DATAFEED_PROVIDER',
    ];

    public function index()
    {
        $settings = SettingsService::all($this->keys);
        $settings = array_merge([
            'CURRENCY' => 'kr',
            'POSITION_SIZE' => 1000,
            'FEE_PERCENT' => 0.001,
            'FEE_MIN' => 2.0,
            'STRATEGY_RANGE_MINUTES' => 15,
            'STRATEGY_REQUIRE_RETEST' => true,
            'ENTRY_BUFFER_PERCENT' => 0.05,
            'SL_BUFFER_PERCENT' => 0.05,
            'SESSION_START' => '09:30',
            'SESSION_END' => '16:00',
            'DATAFEED_PROVIDER' => 'YAHOO',
        ], array_filter($settings, fn($v) => $v !== null));
        return view('settings', compact('settings'));
    }

    public function store(Request $r)
    {
        $data = $r->only($this->keys);

        $data['POSITION_SIZE'] = max(0, (float)($data['POSITION_SIZE'] ?? 1000));
        $data['FEE_PERCENT'] = max(0, (float)($data['FEE_PERCENT'] ?? 0.001));
        $data['FEE_MIN'] = max(0, (float)($data['FEE_MIN'] ?? 2.0));
        $data['ENTRY_BUFFER_PERCENT'] = max(0, (float)($data['ENTRY_BUFFER_PERCENT'] ?? 0.05));
        $data['SL_BUFFER_PERCENT'] = max(0, (float)($data['SL_BUFFER_PERCENT'] ?? 0.05));

        foreach ($data as $k => $v) {
            SettingsService::set($k, $v);
        }
        return redirect()->route('settings')->with('ok', 'Settings saved.');
    }
}

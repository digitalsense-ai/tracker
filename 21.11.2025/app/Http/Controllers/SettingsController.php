<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\SettingsService;
use App\Models\Setting;

class SettingsController extends Controller
{
    public function index(SettingsService $svc)
    {
        $groups = $svc->allByGroups();
        return view('settings.index', compact('groups'));
    }

    public function update(Request $request, SettingsService $svc)
    {
        $items = $request->input('settings', []);

        foreach ($items as $key => $payload) {
            $type  = $payload['type'] ?? 'string';
            $group = $payload['group'] ?? null;
            $label = $payload['label'] ?? null;
            $val   = $payload['value'] ?? null;

            // Normalise checkboxes
            if ($type === 'bool') {
                $val = isset($val) && ($val === '1' || $val === 1 || $val === true || $val === 'on');
            }

            $svc->set($key, $val, $type, $group, $label);
        }

        return redirect()->route('settings.index')->with('ok', 'Settings saved');
    }
}

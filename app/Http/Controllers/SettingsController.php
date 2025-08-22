<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SettingsService;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = SettingsService::all();
        return view('settings', compact('settings'));
    }

    public function store(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {
            SettingsService::set($key, $value);
        }

        return redirect()->route('settings')->with('success', 'Settings saved!');
    }
}

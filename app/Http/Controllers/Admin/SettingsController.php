<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit()
    {
        $settings = [
            'telegram_bot_token' => Setting::get('telegram_bot_token'),
            'telegram_bot_username' => Setting::get('telegram_bot_username'),
            'zenmoney_token' => Setting::get('zenmoney_token'),
        ];

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'telegram_bot_token' => 'required|string',
            'telegram_bot_username' => 'required|string',
            'zenmoney_token' => 'required|string',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('admin.settings.edit')
            ->with('success', 'Settings updated successfully');
    }
}

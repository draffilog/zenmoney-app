<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelegramChat;
use App\Models\Setting;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_chats' => TelegramChat::count(),
            'bot_status' => Setting::get('telegram_bot_token') ? 'Configured' : 'Not Configured',
            'zenmoney_status' => Setting::get('zenmoney_token') ? 'Connected' : 'Not Connected'
        ];

        return view('admin.dashboard', compact('stats'));
    }
}

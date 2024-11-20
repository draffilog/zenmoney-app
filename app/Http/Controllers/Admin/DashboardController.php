<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelegramChat;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        Log::debug('DashboardController: Starting index method');

        try {
            $stats = [
                'total_chats' => TelegramChat::count(),
                'bot_status' => Setting::get('telegram_bot_token') ? 'Configured' : 'Not Configured',
                'zenmoney_status' => Setting::get('zenmoney_token') ? 'Connected' : 'Not Connected'
            ];

            Log::debug('DashboardController: Stats collected', $stats);

            return view('admin.dashboard', compact('stats'));
        } catch (\Exception $e) {
            Log::error('DashboardController: Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}

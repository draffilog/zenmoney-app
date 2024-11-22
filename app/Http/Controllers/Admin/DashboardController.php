<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelegramChat;
use App\Models\ZenmoneyAccount;

class DashboardController extends Controller
{
    public function index()
    {
        $chats = TelegramChat::with('expenseCategories')->get();
        $accounts = ZenmoneyAccount::pluck('name', 'code_zenmoney_account')->toArray();

        return view('admin.dashboard', compact('chats', 'accounts'));
    }
}

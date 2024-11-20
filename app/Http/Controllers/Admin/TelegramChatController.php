<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelegramChat;
use App\Services\ZenMoneyService;
use Illuminate\Http\Request;

class TelegramChatController extends Controller
{
    protected ZenMoneyService $zenMoneyService;

    public function __construct(ZenMoneyService $zenMoneyService)
    {
        $this->zenMoneyService = $zenMoneyService;
    }

    public function index()
    {
        $chats = TelegramChat::all();
        return view('admin.chats.index', compact('chats'));
    }

    public function create()
    {
        $accounts = $this->zenMoneyService->getAccounts();
        $categories = $this->zenMoneyService->getCategories();

        return view('admin.chats.create', compact('accounts', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'chat_id' => 'required|string|unique:telegram_chats',
            'transaction_account_id' => 'required|string',
            'deposit_account_id' => 'required|string',
            'allowed_categories' => 'required|array',
        ]);

        TelegramChat::create($validated);

        return redirect()->route('admin.chats.index')
            ->with('success', 'Chat settings saved successfully');
    }
}

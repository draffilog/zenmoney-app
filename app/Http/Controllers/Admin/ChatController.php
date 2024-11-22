<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelegramChat;
use App\Models\ZenmoneyAccount;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    private $expenseCategories = [
        [
            'id' => 'business',
            'name' => 'Бизнес',
            'type' => 'folder',
            'children' => [
                [
                    'id' => 'ip_veselov',
                    'name' => 'ИП Веселов',
                    'type' => 'category',
                    'parent_id' => 'business'
                ],
                [
                    'id' => 'pools',
                    'name' => 'Бассейны',
                    'type' => 'category',
                    'parent_id' => 'business'
                ],
                [
                    'id' => 'oscar',
                    'name' => 'Оскар',
                    'type' => 'category',
                    'parent_id' => 'business'
                ],
                [
                    'id' => 'loans',
                    'name' => 'Процентные займы',
                    'type' => 'category',
                    'parent_id' => 'business'
                ],
            ]
        ],
        [
            'id' => 'investments',
            'name' => 'Инвестиции',
            'type' => 'folder',
            'children' => [
                [
                    'id' => 'bugrovka94',
                    'name' => 'Бугровка 94',
                    'type' => 'category',
                    'parent_id' => 'investments'
                ],
                [
                    'id' => 'green_meadow',
                    'name' => 'Зеленая поляна',
                    'type' => 'category',
                    'parent_id' => 'investments'
                ],
                [
                    'id' => 'apartment',
                    'name' => 'Квартира',
                    'type' => 'category',
                    'parent_id' => 'investments'
                ],
                [
                    'id' => 'island',
                    'name' => 'Остров',
                    'type' => 'category',
                    'parent_id' => 'investments'
                ]
            ]
        ],
        [
            'id' => 'personal',
            'name' => 'Личные',
            'type' => 'folder',
            'children' => [
                [
                    'id' => 'food',
                    'name' => 'Еда',
                    'type' => 'category',
                    'parent_id' => 'personal'
                ],
                [
                    'id' => 'transport',
                    'name' => 'Транспорт',
                    'type' => 'category',
                    'parent_id' => 'personal'
                ],
                [
                    'id' => 'entertainment',
                    'name' => 'Развлечения',
                    'type' => 'category',
                    'parent_id' => 'personal'
                ]
            ]
        ]
    ];

    public function create()
    {
        return view('admin.chats.create', [
            'zenmoneyAccounts' => ZenmoneyAccount::all(),
            'expenseCategories' => $this->expenseCategories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'telegram_chat_id' => 'required|string|max:255',
            'zenmoney_account' => 'required|string',
            'transit_account' => 'required|string',
            'expense_categories' => 'required|array',
        ]);

        $chat = TelegramChat::create([
            'name' => $validated['name'],
            'telegram_chat_id' => $validated['telegram_chat_id'],
            'zenmoney_account' => $validated['zenmoney_account'],
            'transit_account' => $validated['transit_account'],
        ]);

        $categoryIds = ExpenseCategory::whereIn('name', $validated['expense_categories'])
            ->pluck('id')
            ->toArray();

        $chat->expenseCategories()->attach($categoryIds);

        return redirect()->route('admin.dashboard')
            ->with('status', 'Chat created successfully.');
    }
}

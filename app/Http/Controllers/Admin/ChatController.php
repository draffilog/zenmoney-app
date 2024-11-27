<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelegramChat;
use App\Models\ZenmoneyAccount;
use App\Models\ExpenseCategory;
use App\Services\ZenMoneyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    protected $zenMoneyService;

    public function __construct(ZenMoneyService $zenMoneyService)
    {
        $this->zenMoneyService = $zenMoneyService;
    }

    public function create()
    {
        try {
            $accounts = $this->zenMoneyService->getAccounts();
            $categories = $this->zenMoneyService->getCategories();

            \Log::debug('ZenMoney accounts from API:', [
                'accounts_count' => count($accounts),
                'accounts' => $accounts
            ]);

            return view('admin.chats.create', [
                'zenmoneyAccounts' => $accounts,
                'expenseCategories' => $categories
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch ZenMoney data:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'Не удалось загрузить данные из ZenMoney. Пожалуйста, попробуйте позже.']);
        }
    }

    public function store(Request $request)
    {
        \Log::info('Received request data:', $request->all());

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'telegram_chat_id' => 'required|string|max:255',
            'zenmoney_account' => 'required|string',
            'transit_account' => 'required|string',
            'expense_categories' => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            // Создаем или обновляем записи в таблице zenmoney_accounts
            $zenmoneyAccount = ZenmoneyAccount::updateOrCreate(
                ['code_zenmoney_account' => $validated['zenmoney_account']],
                ['name' => $this->getAccountName($validated['zenmoney_account'])]
            );

            $transitAccount = ZenmoneyAccount::updateOrCreate(
                ['code_zenmoney_account' => $validated['transit_account']],
                ['name' => $this->getAccountName($validated['transit_account'])]
            );

            $chat = TelegramChat::create([
                'name' => $validated['name'],
                'telegram_chat_id' => $validated['telegram_chat_id'],
                'zenmoney_account_id' => $zenmoneyAccount->id,
                'transit_account_id' => $transitAccount->id,
            ]);

            $categoryIds = ExpenseCategory::whereIn('code', $validated['expense_categories'])
                ->pluck('id')
                ->toArray();

            $chat->expenseCategories()->attach($categoryIds);

            DB::commit();

            return redirect()->route('admin.dashboard')
                ->with('status', 'Chat created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create chat: ' . $e->getMessage());

            return back()
                ->withInput()
                ->withErrors(['error' => 'Не удалось создать чат. Пожалуйста, попробуйте снова.']);
        }
    }

    protected function getAccountName(string $accountId): string
    {
        $accounts = $this->zenMoneyService->getAccounts();
        $account = collect($accounts)->firstWhere('id', $accountId);
        return $account['name'] ?? 'Unknown Account';
    }

    public function show(TelegramChat $chat)
    {
        $chat->load('expenseCategories');

        // Получаем коды выбранных категорий
        $selectedCategories = $chat->expenseCategories->pluck('code')->toArray();

        return view('admin.chats.show', [
            'chat' => $chat,
            'expenseCategories' => $this->zenMoneyService->getCategories(),
            'selectedCategories' => $selectedCategories
        ]);
    }

    public function edit(TelegramChat $chat)
    {
        $chat->load('expenseCategories');

        $dbCategories = ExpenseCategory::all();

        $expenseCategories = [
            [
                'code' => 'business',
                'name' => 'Бизнес',
                'type' => 'folder',
                'children' => $dbCategories->where('parent_code', 'business')
                    ->map(function($category) {
                        return [
                            'code' => $category->code,
                            'name' => $category->name,
                            'type' => $category->type,
                            'parent_code' => $category->parent_code
                        ];
                    })->values()->toArray()
            ],
            [
                'code' => 'investments',
                'name' => 'Инвестиции',
                'type' => 'folder',
                'children' => $dbCategories->where('parent_code', 'investments')
                    ->map(function($category) {
                        return [
                            'code' => $category->code,
                            'name' => $category->name,
                            'type' => $category->type,
                            'parent_code' => $category->parent_code
                        ];
                    })->values()->toArray()
            ],
            [
                'code' => 'personal',
                'name' => 'Личные',
                'type' => 'folder',
                'children' => $dbCategories->where('parent_code', 'personal')
                    ->map(function($category) {
                        return [
                            'code' => $category->code,
                            'name' => $category->name,
                            'type' => $category->type,
                            'parent_code' => $category->parent_code
                        ];
                    })->values()->toArray()
            ]
        ];

        \Log::info('Categories structure:', $expenseCategories);
        \Log::info('Chat categories:', [
            'chat_id' => $chat->id,
            'categories' => $chat->expenseCategories->pluck('code')->toArray()
        ]);

        return view('admin.chats.edit', [
            'chat' => $chat,
            'zenmoneyAccounts' => ZenmoneyAccount::all(),
            'expenseCategories' => $expenseCategories,
        ]);
    }

    public function update(Request $request, TelegramChat $chat)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'telegram_chat_id' => 'required|string',
            'zenmoney_account' => 'required|exists:zenmoney_accounts,id',
            'transit_account' => 'required|exists:zenmoney_accounts,id',
            'expense_categories' => 'array',
        ]);

        try {
            DB::beginTransaction();

            \Log::info('Request data:', $request->all());

            $updated = $chat->update([
                'name' => $validated['name'],
                'telegram_chat_id' => $validated['telegram_chat_id'],
                'zenmoney_account_id' => $validated['zenmoney_account'],
                'transit_account_id' => $validated['transit_account'],
            ]);

            $selectedCategories = $request->input('expense_categories', []);
            \Log::info('Selected categories from request:', $selectedCategories);

            if (!empty($selectedCategories)) {
                $categoryIds = ExpenseCategory::whereIn('code', $selectedCategories)
                    ->pluck('id')
                    ->toArray();
                \Log::info('Found category IDs:', $categoryIds);

                $chat->expenseCategories()->sync($categoryIds);
            } else {
                $chat->expenseCategories()->detach();
            }

            DB::commit();

            return redirect()->route('admin.dashboard')
                ->with('success', 'Чат успешно обновлен');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update chat: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()
                ->withInput()
                ->withErrors(['error' => 'Не удалось обновить чат. Пожалуйста, попробуйте снова.']);
        }
    }

    public function destroy(TelegramChat $chat)
    {
        $chat->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Чат успешно удален');
    }
}

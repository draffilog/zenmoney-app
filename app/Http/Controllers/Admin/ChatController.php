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

            // Получаем категории из базы данных
            $expenseCategories = ExpenseCategory::where('type', 'folder')
                ->with('children')
                ->get()
                ->map(function ($folder) {
                    return [
                        'code' => $folder->code,
                        'name' => $folder->name,
                        'type' => 'folder',
                        'children' => $folder->children->map(function ($category) {
                            return [
                                'code' => $category->code,
                                'name' => $category->name,
                                'type' => 'category',
                                'parent_code' => $category->parent_code
                            ];
                        })->toArray()
                    ];
                })
                ->toArray();

            return view('admin.chats.create', [
                'zenmoneyAccounts' => $accounts,
                'expenseCategories' => $expenseCategories
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch data:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'Не удалось загрузить данные. Пожалуйста, попробуйте позже.']);
        }
    }

    public function store(Request $request)
    {
        \Log::info('Starting chat creation with data:', $request->all());

        try {
            // Валидация с детальным логированием
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'telegram_chat_id' => 'required|string|max:255',
                'zenmoney_account' => 'required|string',
                'transit_account' => 'required|string',
                'expense_categories' => 'required|array',
            ]);

            \Log::info('Validation passed. Validated data:', $validated);

            DB::beginTransaction();

            // Создание ZenMoney аккаунтов
            \Log::info('Creating ZenMoney account with data:', [
                'code' => $validated['zenmoney_account'],
                'name' => $this->getAccountName($validated['zenmoney_account'])
            ]);

            $zenmoneyAccount = ZenmoneyAccount::updateOrCreate(
                ['code_zenmoney_account' => $validated['zenmoney_account']],
                ['name' => $this->getAccountName($validated['zenmoney_account'])]
            );

            \Log::info('Created ZenMoney account:', $zenmoneyAccount->toArray());

            $transitAccount = ZenmoneyAccount::updateOrCreate(
                ['code_zenmoney_account' => $validated['transit_account']],
                ['name' => $this->getAccountName($validated['transit_account'])]
            );

            \Log::info('Created Transit account:', $transitAccount->toArray());

            // Создание чата
            $chatData = [
                'name' => $validated['name'],
                'telegram_chat_id' => $validated['telegram_chat_id'],
                'zenmoney_account_id' => $zenmoneyAccount->id,
                'transit_account_id' => $transitAccount->id,
            ];

            \Log::info('Creating chat with data:', $chatData);

            $chat = TelegramChat::create($chatData);

            \Log::info('Created chat:', $chat->toArray());

            // Привязка категорий
            \Log::info('Finding category IDs for codes:', $validated['expense_categories']);

            $categoryIds = ExpenseCategory::whereIn('code', $validated['expense_categories'])
                ->pluck('id')
                ->toArray();

            \Log::info('Found category IDs:', $categoryIds);

            $chat->expenseCategories()->attach($categoryIds);

            \Log::info('Attached categories to chat');

            DB::commit();

            \Log::info('Chat creation completed successfully');

            return redirect()->route('admin.dashboard')
                ->with('status', 'Chat created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create chat:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Не удалось создать чат: ' . $e->getMessage()]);
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
        try {
            $chat->load('expenseCategories');
            $accounts = $this->zenMoneyService->getAccounts();

            // Получаем категории из базы данных так же, как в методе create
            $expenseCategories = ExpenseCategory::where('type', 'folder')
                ->with('children')
                ->get()
                ->map(function ($folder) {
                    return [
                        'code' => $folder->code,
                        'name' => $folder->name,
                        'type' => 'folder',
                        'children' => $folder->children->map(function ($category) {
                            return [
                                'code' => $category->code,
                                'name' => $category->name,
                                'type' => 'category',
                                'parent_code' => $category->parent_code
                            ];
                        })->toArray()
                    ];
                })
                ->toArray();

            \Log::info('Edit chat data:', [
                'chat_id' => $chat->id,
                'selected_categories' => $chat->expenseCategories->pluck('code')->toArray(),
                'available_categories' => $expenseCategories
            ]);

            return view('admin.chats.edit', [
                'chat' => $chat,
                'zenmoneyAccounts' => $accounts,
                'expenseCategories' => $expenseCategories,
                'selectedCategories' => $chat->expenseCategories->pluck('code')->toArray()
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to load edit form:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'Не удалось загрузить форму редактирования. Пожалуйста, попробуйте позже.']);
        }
    }

    public function update(Request $request, TelegramChat $chat)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'telegram_chat_id' => 'required|string',
            'zenmoney_account' => 'required|string',
            'transit_account' => 'required|string',
            'expense_categories' => 'array',
        ]);

        try {
            DB::beginTransaction();

            \Log::info('Request data:', $request->all());

            // Создаем или обновляем аккаунты ZenMoney
            $zenmoneyAccount = ZenmoneyAccount::updateOrCreate(
                ['code_zenmoney_account' => $validated['zenmoney_account']],
                ['name' => $this->getAccountName($validated['zenmoney_account'])]
            );

            $transitAccount = ZenmoneyAccount::updateOrCreate(
                ['code_zenmoney_account' => $validated['transit_account']],
                ['name' => $this->getAccountName($validated['transit_account'])]
            );

            $updated = $chat->update([
                'name' => $validated['name'],
                'telegram_chat_id' => $validated['telegram_chat_id'],
                'zenmoney_account_id' => $zenmoneyAccount->id,
                'transit_account_id' => $transitAccount->id,
            ]);

            if (!empty($validated['expense_categories'])) {
                $categoryIds = ExpenseCategory::whereIn('code', $validated['expense_categories'])
                    ->pluck('id')
                    ->toArray();
                $chat->expenseCategories()->sync($categoryIds);
            } else {
                $chat->expenseCategories()->detach();
            }

            DB::commit();

            return redirect()->route('admin.dashboard')
                ->with('success', 'Чат успешно обновлен');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update chat:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Не удалось обновить чат: ' . $e->getMessage()]);
        }
    }

    public function destroy(TelegramChat $chat)
    {
        $chat->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Чат успешно удален');
    }
}

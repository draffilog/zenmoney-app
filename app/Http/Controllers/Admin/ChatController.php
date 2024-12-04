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

            // Получаем только родительские категории с их подкатегориями
            $categories = ExpenseCategory::where('type', 'folder')
                ->with('children')
                ->get()
                ->map(function ($folder) {
                    return [
                        'id' => $folder->id,
                        'code' => $folder->code,
                        'name' => $folder->name,
                        'type' => 'folder',
                        'children' => $folder->children->map(function ($category) {
                            return [
                                'id' => $category->id,
                                'code' => $category->code,
                                'name' => $category->name,
                                'type' => 'category'
                            ];
                        })
                    ];
                });

            return view('admin.chats.create', [
                'zenmoneyAccounts' => $accounts,
                'categories' => $categories
            ]);
        } catch (\Exception $e) {

            return back()->withErrors(['error' => 'Не удалось загрузить данные']);
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'telegram_chat_id' => 'required|string',
                'zenmoney_account_id' => 'required|string',
                'transit_account_id' => 'required|string',
                'expense_categories' => 'required|array',
            ]);

            // Получаем данные аккаунтов из ZenMoney API
            $accounts = $this->zenMoneyService->getAccounts();
            $zenmoneyAccountData = collect($accounts)->firstWhere('id', $validated['zenmoney_account_id']);
            $transitAccountData = collect($accounts)->firstWhere('id', $validated['transit_account_id']);

            if (!$zenmoneyAccountData || !$transitAccountData) {
                throw new \Exception('Один или оба аккаунта не найдены в ZenMoney');
            }

            // Создаем или обновляем аккаунты в базе данных
            $zenmoneyAccount = ZenmoneyAccount::updateOrCreate(
                ['code_zenmoney_account' => $zenmoneyAccountData['id']],
                [
                    'id' => $zenmoneyAccountData['id'],
                    'name' => $zenmoneyAccountData['name']
                ]
            );

            $transitAccount = ZenmoneyAccount::updateOrCreate(
                ['code_zenmoney_account' => $transitAccountData['id']],
                [
                    'id' => $transitAccountData['id'],
                    'name' => $transitAccountData['name']
                ]
            );

            // Создаем чат
            $chat = TelegramChat::create([
                'name' => $validated['name'],
                'telegram_chat_id' => $validated['telegram_chat_id'],
                'zenmoney_account_id' => $zenmoneyAccount->id,
                'transit_account_id' => $transitAccount->id,
            ]);

            // Прикрепляем категории напрямую по их ID
            if (!empty($validated['expense_categories'])) {
                $chat->expenseCategories()->attach($validated['expense_categories']);
            }

            DB::commit();

            return redirect()->route('admin.chats.index')
                ->with('success', 'Чат успешно создан');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Не удалось создать чат: ' . $e->getMessage()]);
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


            return view('admin.chats.edit', [
                'chat' => $chat,
                'zenmoneyAccounts' => $accounts,
                'expenseCategories' => $expenseCategories,
                'selectedCategories' => $chat->expenseCategories->pluck('code')->toArray()
            ]);
        } catch (\Exception $e) {


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

    public function index()
    {
        $chats = TelegramChat::with(['zenmoneyAccount', 'transitAccount'])->get();
        return view('admin.chats.index', compact('chats'));
    }
}

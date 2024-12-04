<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TelegramChat extends Model
{
    protected $fillable = [
        'name',
        'telegram_chat_id',
        'zenmoney_account_id',
        'transit_account_id'
    ];

    public function zenmoneyAccount(): BelongsTo
    {
        return $this->belongsTo(ZenmoneyAccount::class, 'zenmoney_account_id');
    }

    public function transitAccount(): BelongsTo
    {
        return $this->belongsTo(ZenmoneyAccount::class, 'transit_account_id');
    }

    public function expenseCategories(): BelongsToMany
    {
        return $this->belongsToMany(ExpenseCategory::class, 'telegram_chat_expense_category');
    }

    /**
     * Получить все доступные категории расходов для чата
     */
    public function getAvailableCategories()
    {
        return $this->expenseCategories()
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'full_path' => $category->getFullPath(),
                    'is_leaf' => $category->isLeaf(),
                ];
            });
    }

    /**
     * Проверить, доступна ли категория в этом чате
     */
    public function hasCategoryWithCode(string $code): bool
    {
        return $this->expenseCategories()
            ->where('code', $code)
            ->exists();
    }

    /**
     * Добавить категории расходов по умолчанию для чата
     */
    public function addDefaultExpenseCategories()
    {
        // Получаем все категории верхнего уровня (без parent_code)
        $defaultCategories = ExpenseCategory::whereNull('parent_code')->get();

        // Прикрепляем их к чату
        $this->expenseCategories()->attach($defaultCategories->pluck('id'));

        return $this;
    }

    /**
     * Добавить конкретную категорию расходов к чату
     */
    public function addExpenseCategory($categoryCode)
    {
        $category = ExpenseCategory::where('code', $categoryCode)->first();
        if ($category && !$this->hasCategoryWithCode($categoryCode)) {
            $this->expenseCategories()->attach($category->id);
        }
        return $this;
    }
}

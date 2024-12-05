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
    public function getAvailableCategories($parentCode = null)
    {
        $query = $this->expenseCategories()
            ->orderBy('name');

        if ($parentCode === null) {
            // Получаем только категории верхнего уровня
            $query->whereNull('parent_code');
        } else {
            // Получаем подкатегории для указанной родительской категории
            $query->where('parent_code', $parentCode);
        }

        return $query->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'full_path' => $category->getFullPath(),
                    'is_leaf' => $category->isLeaf(),
                    'type' => $category->type,
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
        if (!$category) {
            return $this;
        }

        // Если категория еще не привязана к чату
        if (!$this->hasCategoryWithCode($categoryCode)) {
            $this->expenseCategories()->attach($category->id);

            // Если это подкатегория, добавляем также родительскую категорию
            if ($category->parent_code) {
                $parentCategory = ExpenseCategory::where('code', $category->parent_code)->first();
                if ($parentCategory && !$this->hasCategoryWithCode($parentCategory->code)) {
                    $this->expenseCategories()->attach($parentCategory->id);
                }
            }
        }

        return $this;
    }
}

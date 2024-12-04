<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ExpenseCategory extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'parent_code'
    ];

    public function parent()
    {
        return $this->belongsTo(ExpenseCategory::class, 'parent_code', 'code');
    }

    public function children()
    {
        return $this->hasMany(ExpenseCategory::class, 'parent_code', 'code');
    }

    public function telegramChats(): BelongsToMany
    {
        return $this->belongsToMany(TelegramChat::class, 'telegram_chat_expense_category');
    }

    /**
     * Получить полный путь категории (включая родительские категории)
     */
    public function getFullPath(): string
    {
        $path = [$this->name];
        $current = $this->parent;

        while ($current) {
            array_unshift($path, $current->name);
            $current = $current->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Получить все доступные подкатегории (включая текущую)
     */
    public function getAllChildren()
    {
        $children = collect([$this]);

        foreach ($this->children as $child) {
            $children = $children->merge($child->getAllChildren());
        }

        return $children;
    }

    /**
     * Проверить, является ли категория конечной (без подкатегорий)
     */
    public function isLeaf(): bool
    {
        return $this->children()->count() === 0;
    }
}

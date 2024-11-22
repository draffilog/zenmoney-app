<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TelegramChat extends Model
{
    protected $fillable = [
        'name',
        'telegram_chat_id',
        'zenmoney_account',
        'transit_account'
    ];

    public function expenseCategories(): BelongsToMany
    {
        return $this->belongsToMany(ExpenseCategory::class, 'telegram_chat_expense_category');
    }
}

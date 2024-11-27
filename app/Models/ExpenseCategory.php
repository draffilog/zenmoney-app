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

    public function telegramChats(): BelongsToMany
    {
        return $this->belongsToMany(TelegramChat::class, 'telegram_chat_expense_category');
    }
}

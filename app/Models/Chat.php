<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $table = 'telegram_chats';

    protected $fillable = [
        'telegram_chat_id',
        'zenmoney_account_id',
        'account_name',
    ];

    public function allowedCategories()
    {
        return $this->belongsToMany(ExpenseCategory::class, 'telegram_chat_expense_category');
    }
}

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
        return $this->belongsToMany(ExpenseCategory::class);
    }
}

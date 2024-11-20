<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramChat extends Model
{
    protected $fillable = [
        'name',
        'chat_id',
        'transaction_account_id',
        'deposit_account_id',
    ];

    protected $casts = [
        'allowed_categories' => 'array',
    ];

    public function categories(): HasMany
    {
        return $this->hasMany(AllowedCategory::class);
    }
}

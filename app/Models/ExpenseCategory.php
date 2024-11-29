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
        return $this->belongsToMany(TelegramChat::class);
    }
}

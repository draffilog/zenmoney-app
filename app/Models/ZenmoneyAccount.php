<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZenmoneyAccount extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'code_zenmoney_account'
    ];

    /**
     * Получить чаты Telegram, использующие этот аккаунт
     */
    public function telegramChats()
    {
        return $this->hasMany(TelegramChat::class, 'zenmoney_account_id');
    }

    /**
     * Получить транзитные чаты Telegram
     */
    public function transitTelegramChats()
    {
        return $this->hasMany(TelegramChat::class, 'transit_account_id');
    }
}


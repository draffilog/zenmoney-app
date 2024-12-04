<?php

namespace Database\Seeders;

use App\Models\ZenmoneyAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZenmoneyAccountsSeeder extends Seeder
{
    public function run()
    {
        $accounts = [
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Счёт Вероники',
                'code_zenmoney_account' => 'account1'
            ],
            [
                'id' => Str::uuid()->toString(),
                'name' => 'Транзитный счёт',
                'code_zenmoney_account' => 'account2'
            ],
            // Добавьте другие тестовые аккаунты если нужно
        ];

        foreach ($accounts as $account) {
            ZenmoneyAccount::create($account);
        }
    }
}


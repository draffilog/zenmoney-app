<?php

namespace Database\Seeders;

use App\Models\ZenmoneyAccount;
use Illuminate\Database\Seeder;

class ZenmoneyAccountsSeeder extends Seeder
{
    public function run()
    {
        $accounts = [
            [
                'name' => 'Счёт Вероники',
                'code_zenmoney_account' => 'account1'
            ],
            [
                'name' => 'Общий счет',
                'code_zenmoney_account' => 'account2'
            ],
            [
                'name' => 'Транзитный счет',
                'code_zenmoney_account' => 'account3'
            ],
        ];

        foreach ($accounts as $account) {
            ZenmoneyAccount::create($account);
        }
    }
}


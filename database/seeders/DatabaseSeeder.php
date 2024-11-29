<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            AdminSeeder::class,
            ExpenseCategoriesSeeder::class,
            ZenmoneyAccountsSeeder::class,
        ]);

        // Синхронизация категорий из ZenMoney
        $this->call(ZenMoneyCategoriesSeeder::class);
    }
}

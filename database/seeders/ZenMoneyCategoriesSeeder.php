<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class ZenMoneyCategoriesSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Синхронизация категорий из ZenMoney...');
        Artisan::call('zenmoney:sync-categories');
    }
}

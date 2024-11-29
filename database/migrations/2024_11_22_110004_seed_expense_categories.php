<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Очищаем существующие данные
        DB::statement('TRUNCATE telegram_chat_expense_category CASCADE');
        DB::statement('TRUNCATE expense_categories CASCADE');

        // Добавляем базовые категории
        $categories = [
            [
                'code' => 'business',
                'name' => 'Бизнес',
                'type' => 'folder',
                'children' => [
                    ['code' => 'ip_veselov', 'name' => 'ИП Веселов'],
                    ['code' => 'pools', 'name' => 'Бассейны'],
                    ['code' => 'oscar', 'name' => 'Оскар'],
                    ['code' => 'loans', 'name' => 'Процентные займы'],
                ]
            ],
            // ... остальные категории ...
        ];

        foreach ($categories as $folder) {
            DB::table('expense_categories')->insert([
                'code' => $folder['code'],
                'name' => $folder['name'],
                'type' => 'folder',
                'parent_code' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($folder['children'] as $child) {
                DB::table('expense_categories')->insert([
                    'code' => $child['code'],
                    'name' => $child['name'],
                    'type' => 'category',
                    'parent_code' => $folder['code'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('expense_categories')->truncate();
    }
};

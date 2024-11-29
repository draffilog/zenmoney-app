<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First drop the foreign key constraint
        Schema::table('telegram_chat_expense_category', function (Blueprint $table) {
            $table->dropForeign(['expense_category_id']);
        });

        // Now you can safely drop the expense_categories table
        Schema::dropIfExists('expense_categories');

        // Пересоздаем таблицу с правильной структурой
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type');  // folder или category
            $table->string('parent_code')->nullable();
            $table->timestamps();

            $table->index('parent_code');
            $table->index('type');
        });

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
            [
                'code' => 'investments',
                'name' => 'Инвестиции',
                'type' => 'folder',
                'children' => [
                    ['code' => 'bugrovka94', 'name' => 'Бугровка 94'],
                    ['code' => 'green_meadow', 'name' => 'Зеленая поляна'],
                    ['code' => 'apartment', 'name' => 'Квартира'],
                    ['code' => 'island', 'name' => 'Остров'],
                ]
            ],
            [
                'code' => 'personal',
                'name' => 'Личные',
                'type' => 'folder',
                'children' => [
                    ['code' => 'food', 'name' => 'Еда'],
                    ['code' => 'transport', 'name' => 'Транспорт'],
                    ['code' => 'entertainment', 'name' => 'Развлечения'],
                ]
            ]
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
        Schema::dropIfExists('expense_categories');
    }
};

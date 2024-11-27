<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\ExpenseCategory;

return new class extends Migration
{
    public function up(): void
    {
        // Для PostgreSQL используем другой способ отключения ограничений
        DB::statement('ALTER TABLE expense_category_telegram_chat DISABLE TRIGGER ALL;');
        DB::statement('ALTER TABLE expense_categories DISABLE TRIGGER ALL;');

        // Очищаем существующие данные
        DB::table('expense_category_telegram_chat')->delete();
        DB::table('expense_categories')->delete();

        // Заполняем категории
        $categories = [
            [
                'code' => 'business',
                'name' => 'Бизнес',
                'type' => 'folder',
                'children' => [
                    [
                        'code' => 'ip_veselov',
                        'name' => 'ИП Веселов',
                        'type' => 'category',
                    ],
                    [
                        'code' => 'pools',
                        'name' => 'Бассейны',
                        'type' => 'category',
                    ],
                    [
                        'code' => 'oscar',
                        'name' => 'Оскар',
                        'type' => 'category',
                    ],
                    [
                        'code' => 'loans',
                        'name' => 'Процентные займы',
                        'type' => 'category',
                    ],
                ]
            ],
            [
                'code' => 'investments',
                'name' => 'Инвестиции',
                'type' => 'folder',
                'children' => [
                    [
                        'code' => 'bugrovka94',
                        'name' => 'Бугровка 94',
                        'type' => 'category',
                    ],
                    [
                        'code' => 'green_meadow',
                        'name' => 'Зеленая поляна',
                        'type' => 'category',
                    ],
                    [
                        'code' => 'apartment',
                        'name' => 'Квартира',
                        'type' => 'category',
                    ],
                    [
                        'code' => 'island',
                        'name' => 'Остров',
                        'type' => 'category',
                    ]
                ]
            ],
            [
                'code' => 'personal',
                'name' => 'Личные',
                'type' => 'folder',
                'children' => [
                    [
                        'code' => 'food',
                        'name' => 'Еда',
                        'type' => 'category',
                    ],
                    [
                        'code' => 'transport',
                        'name' => 'Транспорт',
                        'type' => 'category',
                    ],
                    [
                        'code' => 'entertainment',
                        'name' => 'Развлечения',
                        'type' => 'category',
                    ]
                ]
            ]
        ];

        foreach ($categories as $folder) {
            ExpenseCategory::create([
                'code' => $folder['code'],
                'name' => $folder['name'],
                'type' => $folder['type'],
            ]);

            foreach ($folder['children'] as $category) {
                ExpenseCategory::create([
                    'code' => $category['code'],
                    'name' => $category['name'],
                    'type' => $category['type'],
                    'parent_code' => $folder['code'],
                ]);
            }
        }

        // Включаем обратно все триггеры
        DB::statement('ALTER TABLE expense_category_telegram_chat ENABLE TRIGGER ALL;');
        DB::statement('ALTER TABLE expense_categories ENABLE TRIGGER ALL;');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE expense_category_telegram_chat DISABLE TRIGGER ALL;');
        DB::statement('ALTER TABLE expense_categories DISABLE TRIGGER ALL;');

        DB::table('expense_category_telegram_chat')->delete();
        DB::table('expense_categories')->delete();

        DB::statement('ALTER TABLE expense_category_telegram_chat ENABLE TRIGGER ALL;');
        DB::statement('ALTER TABLE expense_categories ENABLE TRIGGER ALL;');
    }
};

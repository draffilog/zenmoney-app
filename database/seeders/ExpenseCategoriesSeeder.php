<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategoriesSeeder extends Seeder
{
    public function run()
    {
        // Создаем родительские категории
        $business = ExpenseCategory::create([
            'code' => 'business',
            'name' => 'Бизнес',
            'type' => 'folder',
            'parent_code' => null
        ]);

        $investments = ExpenseCategory::create([
            'code' => 'investments',
            'name' => 'Инвестиции',
            'type' => 'folder',
            'parent_code' => null
        ]);

        $personal = ExpenseCategory::create([
            'code' => 'personal',
            'name' => 'Личные',
            'type' => 'folder',
            'parent_code' => null
        ]);

        // Создаем подкатегории для Бизнеса
        ExpenseCategory::create([
            'code' => 'ip_veselov',
            'name' => 'ИП Веселов',
            'type' => 'category',
            'parent_code' => 'business'
        ]);

        ExpenseCategory::create([
            'code' => 'pools',
            'name' => 'Бассейны',
            'type' => 'category',
            'parent_code' => 'business'
        ]);

        ExpenseCategory::create([
            'code' => 'oscar',
            'name' => 'Оскар',
            'type' => 'category',
            'parent_code' => 'business'
        ]);

        // Создаем подкатегории для Инвестиций
        ExpenseCategory::create([
            'code' => 'bugrovka94',
            'name' => 'Бугровка 94',
            'type' => 'category',
            'parent_code' => 'investments'
        ]);

        ExpenseCategory::create([
            'code' => 'apartment',
            'name' => 'Квартира',
            'type' => 'category',
            'parent_code' => 'investments'
        ]);

        // Создаем подкатегории для Личных расходов
        ExpenseCategory::create([
            'code' => 'food',
            'name' => 'Еда',
            'type' => 'category',
            'parent_code' => 'personal'
        ]);

        ExpenseCategory::create([
            'code' => 'transport',
            'name' => 'Транспорт',
            'type' => 'category',
            'parent_code' => 'personal'
        ]);

        ExpenseCategory::create([
            'code' => 'entertainment',
            'name' => 'Развлечения',
            'type' => 'category',
            'parent_code' => 'personal'
        ]);
    }
}

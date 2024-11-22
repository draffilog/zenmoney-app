<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategoriesSeeder extends Seeder
{
    public function run()
    {
        // Создаем родительские категории
        $business = ExpenseCategory::create(['name' => 'Бизнес']);
        $investments = ExpenseCategory::create(['name' => 'Инвестиции']);
        $personal = ExpenseCategory::create(['name' => 'Личные']);

        // Создаем подкатегории для Бизнеса
        ExpenseCategory::create(['name' => 'ИП Веселов', 'parent_id' => $business->id]);
        ExpenseCategory::create(['name' => 'Бассейны', 'parent_id' => $business->id]);
        ExpenseCategory::create(['name' => 'Оскар', 'parent_id' => $business->id]);
        ExpenseCategory::create(['name' => 'Процентные займы', 'parent_id' => $business->id]);

        // Создаем подкатегории для Инвестиций
        ExpenseCategory::create(['name' => 'Бугровка 94', 'parent_id' => $investments->id]);
        ExpenseCategory::create(['name' => 'Зеленая поляна', 'parent_id' => $investments->id]);
        ExpenseCategory::create(['name' => 'Квартира', 'parent_id' => $investments->id]);
        ExpenseCategory::create(['name' => 'Остров', 'parent_id' => $investments->id]);

        // Создаем подкатегории для Личных расходов
        ExpenseCategory::create(['name' => 'Еда', 'parent_id' => $personal->id]);
        ExpenseCategory::create(['name' => 'Транспорт', 'parent_id' => $personal->id]);
        ExpenseCategory::create(['name' => 'Развлечения', 'parent_id' => $personal->id]);
    }
}

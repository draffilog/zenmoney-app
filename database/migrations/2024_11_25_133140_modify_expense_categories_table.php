<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Сначала очищаем связующую таблицу
        DB::statement('TRUNCATE expense_category_telegram_chat CASCADE');

        // Затем очищаем основную таблицу
        DB::statement('TRUNCATE expense_categories CASCADE');

        Schema::table('expense_categories', function (Blueprint $table) {
            // Удаляем существующий внешний ключ
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');

            // Добавляем новые колонки
            $table->string('code')->unique()->after('id');
            $table->string('type')->default('category')->after('name');
            $table->string('parent_code')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            // Удаляем новые колонки
            $table->dropColumn(['code', 'type', 'parent_code']);

            // Восстанавливаем старую структуру
            $table->foreignId('parent_id')->nullable()->constrained('expense_categories')->nullOnDelete();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('expense_category_telegram_chat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('telegram_chat_id')
                ->constrained()
                ->onDelete('cascade');
            $table->timestamps();

            // Добавляем уникальный индекс для предотвращения дублей
            $table->unique(['expense_category_id', 'telegram_chat_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('expense_category_telegram_chat');
    }
};

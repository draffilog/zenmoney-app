<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('telegram_chat_expense_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['telegram_chat_id', 'expense_category_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('telegram_chat_expense_category');
    }
};

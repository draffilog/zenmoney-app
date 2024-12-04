<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Сначала удаляем связующую таблицу
        Schema::dropIfExists('telegram_chat_expense_category');

        // Создаем новую таблицу zenmoney_accounts с UUID
        Schema::create('zenmoney_accounts_new', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code_zenmoney_account')->unique();
            $table->timestamps();
        });

        // Создаем новую таблицу telegram_chats с UUID
        Schema::create('telegram_chats_new', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('telegram_chat_id');
            $table->uuid('zenmoney_account_id');
            $table->uuid('transit_account_id');
            $table->timestamps();

            $table->foreign('zenmoney_account_id')
                ->references('id')
                ->on('zenmoney_accounts_new')
                ->onDelete('cascade');

            $table->foreign('transit_account_id')
                ->references('id')
                ->on('zenmoney_accounts_new')
                ->onDelete('cascade');
        });

        // Удаляем старые таблицы
        Schema::dropIfExists('telegram_chats');
        Schema::dropIfExists('zenmoney_accounts');

        // Переименовываем новые таблицы
        Schema::rename('zenmoney_accounts_new', 'zenmoney_accounts');
        Schema::rename('telegram_chats_new', 'telegram_chats');

        // Пересоздаем связующую таблицу
        Schema::create('telegram_chat_expense_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_chat_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('expense_category_id')
                ->constrained()
                ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['telegram_chat_id', 'expense_category_id']);
        });
    }

    public function down()
    {
        // Удаляем связующую таблицу
        Schema::dropIfExists('telegram_chat_expense_category');

        // В случае отката создаем таблицы с прежней структурой
        Schema::create('zenmoney_accounts_old', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code_zenmoney_account')->unique();
            $table->timestamps();
        });

        Schema::create('telegram_chats_old', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('telegram_chat_id');
            $table->foreignId('zenmoney_account_id')
                ->constrained('zenmoney_accounts_old')
                ->onDelete('cascade');
            $table->foreignId('transit_account_id')
                ->constrained('zenmoney_accounts_old')
                ->onDelete('cascade');
            $table->timestamps();
        });

        // Удаляем новые таблицы
        Schema::dropIfExists('telegram_chats');
        Schema::dropIfExists('zenmoney_accounts');

        // Переименовываем старые таблицы обратно
        Schema::rename('zenmoney_accounts_old', 'zenmoney_accounts');
        Schema::rename('telegram_chats_old', 'telegram_chats');

        // Пересоздаем связующую таблицу
        Schema::create('telegram_chat_expense_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_chat_id')
                ->constrained()
                ->onDelete('cascade');
            $table->foreignId('expense_category_id')
                ->constrained()
                ->onDelete('cascade');
            $table->timestamps();

            $table->unique(['telegram_chat_id', 'expense_category_id']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('telegram_chats', function (Blueprint $table) {
            // Сначала удаляем старые колонки, если они существуют
            if (Schema::hasColumn('telegram_chats', 'zenmoney_account')) {
                $table->dropColumn('zenmoney_account');
            }
            if (Schema::hasColumn('telegram_chats', 'transit_account')) {
                $table->dropColumn('transit_account');
            }

            // Добавляем новые колонки для внешних ключей
            if (!Schema::hasColumn('telegram_chats', 'zenmoney_account_id')) {
                $table->foreignId('zenmoney_account_id')
                    ->constrained('zenmoney_accounts')
                    ->onDelete('cascade');
            }

            if (!Schema::hasColumn('telegram_chats', 'transit_account_id')) {
                $table->foreignId('transit_account_id')
                    ->constrained('zenmoney_accounts')
                    ->onDelete('cascade');
            }
        });
    }

    public function down()
    {
        Schema::table('telegram_chats', function (Blueprint $table) {
            $table->dropForeign(['zenmoney_account_id']);
            $table->dropForeign(['transit_account_id']);
            $table->dropColumn(['zenmoney_account_id', 'transit_account_id']);

            $table->string('zenmoney_account')->nullable();
            $table->string('transit_account')->nullable();
        });
    }
};

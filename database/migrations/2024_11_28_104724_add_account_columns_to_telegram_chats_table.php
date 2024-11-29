<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('telegram_chats', function (Blueprint $table) {
            $table->foreignId('zenmoney_account_id')
                ->constrained('zenmoney_accounts')
                ->onDelete('cascade');

            $table->foreignId('transit_account_id')
                ->constrained('zenmoney_accounts')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('telegram_chats', function (Blueprint $table) {
            $table->dropForeign(['zenmoney_account_id']);
            $table->dropForeign(['transit_account_id']);
            $table->dropColumn(['zenmoney_account_id', 'transit_account_id']);
        });
    }
};

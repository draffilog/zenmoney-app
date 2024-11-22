<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('telegram_chats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('telegram_chat_id');
            $table->string('zenmoney_account');
            $table->string('transit_account');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('telegram_chats');
    }
};

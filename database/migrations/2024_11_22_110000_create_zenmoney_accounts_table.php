<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('zenmoney_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code_zenmoney_account')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('zenmoney_accounts');
    }
};


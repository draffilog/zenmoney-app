<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type');  // folder или category
            $table->string('parent_code')->nullable();
            $table->timestamps();

            $table->index('parent_code');
            $table->index('type');
        });
    }

    public function down()
    {
        Schema::dropIfExists('expense_categories');
    }
};

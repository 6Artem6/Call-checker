<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('instruction', function (Blueprint $table) {
            $table->id('instruction_id')->unique();
            $table->unsignedBigInteger('user_id'); // ID пользователя
            $table->unsignedBigInteger('theme_id'); // ID темы
            // Уникальный тройной ключ
            $table->unique(['instruction_text', 'user_id', 'theme_id'], 'instruction_unique');
            $table->string('instruction_text', 256);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instruction');
    }
};

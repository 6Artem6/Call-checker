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
        Schema::create('user', function (Blueprint $table) {
            $table->id('user_id')->unique();
            $table->string('user_name', 50)->unique();
            $table->string('password', 100);
            $table->string('auth_key', 100)->nullable();
            $table->string('access_token', 100)->nullable();
            $table->unsignedTinyInteger('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user');
    }
};

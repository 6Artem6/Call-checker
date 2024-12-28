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
        Schema::create('request', function (Blueprint $table) {
            $table->id('request_id')->unique(); // Primary key
            $table->timestamp('request_datetime')->nullable(); // Datetime field
            $table->unsignedBigInteger('user_id'); // Foreign key-like integer
            $table->unsignedBigInteger('theme_id'); // Foreign key-like integer
            $table->unsignedTinyInteger('status_id'); // Foreign key-like integer
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request');
    }
};

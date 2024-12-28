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
        Schema::create('file_chunk', function (Blueprint $table) {
            $table->id('chunk_id')->unique();
            $table->unsignedInteger('start_milliseconds');
            $table->unsignedInteger('end_milliseconds');
            $table->unsignedTinyInteger('speaker');
            $table->unsignedTinyInteger('confidence');
            $table->unsignedBigInteger('file_id');
            $table->text('text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file_chunk');
    }
};

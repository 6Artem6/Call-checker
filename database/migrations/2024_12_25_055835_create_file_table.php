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
        Schema::create('file', function (Blueprint $table) {
            $table->id('file_id')->unique();
            $table->string('file_name', 1024);
            $table->string('file_ext', 6);
            $table->string('file_hash', 40);
            $table->string('file_system_name', 64);
            $table->unsignedBigInteger('file_size');
            $table->unsignedBigInteger('request_id');
            $table->unsignedTinyInteger('status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('file');
    }
};

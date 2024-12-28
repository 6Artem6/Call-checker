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
        Schema::create('request_instruction', function (Blueprint $table) {
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('instruction_id');
            $table->unique(['request_id', 'instruction_id'], 'unique_request_instruction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_instruction');
    }
};

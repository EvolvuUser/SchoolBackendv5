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
        Schema::create('update_studentdata_settings', function (Blueprint $table) {
            $table->integer('column_id', true);
            $table->string('column_name', 25);
            $table->string('label', 30);
            $table->string('input_type', 30);
            $table->string('data_type', 30);
            $table->integer('max_length')->nullable();
            $table->integer('nullable');
            $table->json('options')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('update_studentdata_settings');
    }
};

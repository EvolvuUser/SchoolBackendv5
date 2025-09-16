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
        Schema::create('background_color', function (Blueprint $table) {
            $table->integer('background_color_id', true);
            $table->string('name', 20);
            $table->string('color_category', 20);
            $table->string('color_code', 60);
            $table->string('is_active', 1)->default('N');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('background_color');
    }
};

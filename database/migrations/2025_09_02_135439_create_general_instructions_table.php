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
        Schema::create('general_instructions', function (Blueprint $table) {
            $table->integer('general_instructions_id', true);
            $table->string('general_instructions', 250);
            $table->char('is_active', 1)->default('Y');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_instructions');
    }
};

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
        Schema::create('lesson_plan_heading', function (Blueprint $table) {
            $table->integer('lesson_plan_headings_id', true);
            $table->string('name', 50);
            $table->integer('sequence');
            $table->char('change_daily', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_heading');
    }
};

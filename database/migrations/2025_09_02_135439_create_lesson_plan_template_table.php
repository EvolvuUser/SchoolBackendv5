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
        Schema::create('lesson_plan_template', function (Blueprint $table) {
            $table->integer('les_pln_temp_id', true);
            $table->integer('class_id');
            $table->integer('subject_id');
            $table->integer('chapter_id');
            $table->integer('reg_id');
            $table->char('publish', 1);
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_template');
    }
};

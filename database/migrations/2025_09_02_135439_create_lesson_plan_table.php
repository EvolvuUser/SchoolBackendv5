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
        Schema::create('lesson_plan', function (Blueprint $table) {
            $table->integer('lesson_plan_id', true);
            $table->integer('unq_id');
            $table->integer('les_pln_temp_id');
            $table->integer('class_id');
            $table->integer('section_id');
            $table->integer('subject_id');
            $table->integer('chapter_id');
            $table->string('no_of_periods', 10);
            $table->string('week_date', 30);
            $table->integer('reg_id');
            $table->string('academic_yr', 11);
            $table->char('status', 1);
            $table->string('approve', 1);
            $table->string('remark', 300);
            $table->char('IsDelete', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plan');
    }
};

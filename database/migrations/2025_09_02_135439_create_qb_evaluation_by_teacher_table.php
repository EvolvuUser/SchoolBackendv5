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
        Schema::create('qb_evaluation_by_teacher', function (Blueprint $table) {
            $table->integer('eval_id', true);
            $table->integer('teacher_id');
            $table->integer('student_id');
            $table->integer('question_bank_id');
            $table->integer('question_id');
            $table->integer('weightage');
            $table->integer('marks_obt');
            $table->timestamp('date')->useCurrentOnUpdate()->useCurrent();
            $table->string('academic_yr', 14);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qb_evaluation_by_teacher');
    }
};

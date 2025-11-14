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
        Schema::create('student_marks', function (Blueprint $table) {
            $table->integer('marks_id', true);
            $table->integer('class_id');
            $table->integer('section_id');
            $table->integer('exam_id');
            $table->string('subject_id', 100);
            $table->integer('student_id');
            $table->date('date');
            $table->string('present', 200);
            $table->string('mark_obtained', 200);
            $table->string('highest_marks', 200);
            $table->string('reportcard_marks', 300)->nullable();
            $table->string('reportcard_highest_marks', 300)->nullable();
            $table->integer('total_marks')->nullable();
            $table->integer('highest_total_marks')->nullable();
            $table->string('grade', 200);
            $table->string('percent', 200);
            $table->char('publish', 1)->default('N');
            $table->string('comment', 200);
            $table->integer('data_entry_by');
            $table->string('academic_yr', 11);

            $table->unique(['class_id', 'section_id', 'exam_id', 'subject_id', 'student_id'], 'class_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_marks');
    }
};

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
        Schema::create('allocate_questionbank', function (Blueprint $table) {
            $table->integer('question_bank_id');
            $table->integer('class_id');
            $table->integer('section_id');
            $table->integer('subject_id');
            $table->integer('teacher_id');
            $table->char('status', 1);
            $table->string('academic_yr', 14);

            $table->index(['question_bank_id', 'class_id', 'section_id', 'subject_id', 'teacher_id'], 'inx_qb_cl_sec_sub_t');
            $table->unique(['question_bank_id', 'class_id', 'section_id', 'academic_yr'], 'question_bank_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allocate_questionbank');
    }
};

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
        Schema::create('exam_question_bank', function (Blueprint $table) {
            $table->integer('question_bank_id', true);
            $table->integer('exam_id');
            $table->integer('class_id');
            $table->string('subject_id', 30);
            $table->string('qb_name', 70);
            $table->string('qb_type', 6);
            $table->string('instructions', 500)->nullable();
            $table->integer('weightage')->nullable();
            $table->timestamp('create_date')->useCurrentOnUpdate()->useCurrent();
            $table->integer('teacher_id');
            $table->char('complete', 1);
            $table->string('academic_yr', 20);

            $table->unique(['question_bank_id', 'exam_id', 'class_id', 'subject_id', 'academic_yr'], 'inx_qb_ex_cl_sub_acdyr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_question_bank');
    }
};

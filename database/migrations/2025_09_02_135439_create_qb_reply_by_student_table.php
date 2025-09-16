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
        Schema::create('qb_reply_by_student', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('student_id');
            $table->integer('question_bank_id');
            $table->integer('question_id');
            $table->string('answer', 300)->nullable();
            $table->timestamp('date')->useCurrentOnUpdate()->useCurrent();
            $table->string('academic_year', 20);
            $table->char('ans_status', 1);
            $table->char('que_attempted', 1);

            $table->index(['student_id', 'question_bank_id', 'question_id'], 'inx_stu_qb_q');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qb_reply_by_student');
    }
};

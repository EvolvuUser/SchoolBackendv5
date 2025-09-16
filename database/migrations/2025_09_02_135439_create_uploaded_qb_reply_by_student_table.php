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
        Schema::create('uploaded_qb_reply_by_student', function (Blueprint $table) {
            $table->integer('up_id')->primary();
            $table->integer('student_id');
            $table->integer('question_bank_id');
            $table->timestamp('date')->useCurrentOnUpdate()->useCurrent();
            $table->string('academic_yr', 20);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploaded_qb_reply_by_student');
    }
};

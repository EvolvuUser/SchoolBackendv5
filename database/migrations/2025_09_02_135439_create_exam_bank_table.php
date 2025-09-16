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
        Schema::create('exam_bank', function (Blueprint $table) {
            $table->integer('exam_bank_id', true);
            $table->integer('exam_id');
            $table->integer('class_id');
            $table->string('subject_id', 30);
            $table->integer('question_bank_id');
            $table->timestamp('create_date')->useCurrentOnUpdate()->useCurrent();
            $table->integer('teacher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_bank');
    }
};

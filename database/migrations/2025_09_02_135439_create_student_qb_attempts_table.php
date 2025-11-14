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
        Schema::create('student_qb_attempts', function (Blueprint $table) {
            $table->integer('student_id');
            $table->integer('question_bank_id');
            $table->timestamp('start_time')->useCurrent();
            $table->timestamp('end_time')->default('0000-00-00 00:00:00');
            $table->char('status', 1);
            $table->string('time_taken', 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_qb_attempts');
    }
};

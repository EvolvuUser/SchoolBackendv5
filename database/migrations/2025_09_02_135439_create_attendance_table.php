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
        Schema::create('attendance', function (Blueprint $table) {
            $table->integer('attendance_id', true);
            $table->integer('unq_id');
            $table->integer('teacher_id');
            $table->integer('class_id');
            $table->integer('section_id');
            $table->integer('subject_id')->nullable();
            $table->dateTime('date');
            $table->integer('student_id');
            $table->char('attendance_status', 1);
            $table->date('only_date');
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};

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
        Schema::create('monthly_attendance', function (Blueprint $table) {
            $table->integer('m_attendance_id', true);
            $table->integer('teacher_id');
            $table->integer('class_id');
            $table->integer('section_id');
            $table->integer('student_id');
            $table->string('month', 10);
            $table->integer('present_days');
            $table->integer('working_days');
            $table->string('academic_yr', 10);

            $table->unique(['class_id', 'section_id', 'student_id', 'month', 'academic_yr'], 'inx_mon_atd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_attendance');
    }
};

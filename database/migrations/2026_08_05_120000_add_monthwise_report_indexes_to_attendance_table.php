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
        Schema::table('attendance', function (Blueprint $table) {
            $table->index(
                ['class_id', 'section_id', 'academic_yr', 'only_date', 'student_id'],
                'attendance_class_section_year_date_student_idx'
            );
            $table->index(
                ['student_id', 'only_date'],
                'attendance_student_date_idx'
            );
            $table->index(
                ['student_id', 'academic_yr', 'only_date'],
                'attendance_student_year_date_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropIndex('attendance_class_section_year_date_student_idx');
            $table->dropIndex('attendance_student_date_idx');
            $table->dropIndex('attendance_student_year_date_idx');
        });
    }
};

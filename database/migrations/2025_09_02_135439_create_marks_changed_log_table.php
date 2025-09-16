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
        Schema::create('marks_changed_log', function (Blueprint $table) {
            $table->integer('marks_changed_id', true);
            $table->integer('exam_id');
            $table->integer('subject_id');
            $table->integer('student_id');
            $table->string('mark_obtained_before', 200);
            $table->string('mark_obtained_after', 200);
            $table->date('date_of_change');
            $table->integer('changed_by');
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marks_changed_log');
    }
};

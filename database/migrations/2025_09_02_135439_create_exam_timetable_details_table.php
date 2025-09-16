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
        Schema::create('exam_timetable_details', function (Blueprint $table) {
            $table->integer('exam_tt_id');
            $table->date('date');
            $table->string('subject_rc_id', 50)->nullable();
            $table->char('study_leave', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_timetable_details');
    }
};

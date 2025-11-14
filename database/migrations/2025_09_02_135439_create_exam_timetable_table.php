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
        Schema::create('exam_timetable', function (Blueprint $table) {
            $table->integer('exam_tt_id', true);
            $table->integer('class_id');
            $table->integer('exam_id');
            $table->string('description', 500)->nullable();
            $table->char('publish', 1);
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_timetable');
    }
};

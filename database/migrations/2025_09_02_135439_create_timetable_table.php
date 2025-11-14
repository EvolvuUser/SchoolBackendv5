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
        Schema::create('timetable', function (Blueprint $table) {
            $table->integer('t_id', true);
            $table->dateTime('date');
            $table->integer('class_id');
            $table->integer('section_id');
            $table->string('time_in', 5);
            $table->string('time_out', 5);
            $table->integer('period_no');
            $table->string('monday', 300);
            $table->string('tuesday', 300);
            $table->string('wednesday', 300);
            $table->string('thursday', 300);
            $table->string('friday', 300);
            $table->string('sat_in', 5)->nullable();
            $table->string('sat_out', 5)->nullable();
            $table->string('saturday', 300)->nullable();
            $table->char('status', 1);
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timetable');
    }
};

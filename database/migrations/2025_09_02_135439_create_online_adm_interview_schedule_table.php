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
        Schema::create('online_adm_interview_schedule', function (Blueprint $table) {
            $table->integer('oadm_int_schedule_id', true);
            $table->string('form_id', 20);
            $table->string('academic_yr', 11);
            $table->date('interview_date');
            $table->string('interview_time_from', 30)->nullable();
            $table->string('interview_time_to', 30)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_adm_interview_schedule');
    }
};

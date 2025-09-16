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
        Schema::create('allot_topic_time', function (Blueprint $table) {
            $table->integer('att_id')->primary();
            $table->integer('chapter_id');
            $table->integer('topic_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('actual_complete_date');
            $table->string('tools', 100);
            $table->string('teaching_aids', 100);
            $table->integer('reg_id');
            $table->string('academic_yr', 9);
            $table->char('publish', 1);
            $table->char('complete', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allot_topic_time');
    }
};

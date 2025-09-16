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
        Schema::create('notice', function (Blueprint $table) {
            $table->integer('notice_id', true);
            $table->integer('unq_id');
            $table->string('subject', 100);
            $table->string('notice_desc', 1000);
            $table->date('notice_date');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('class_id');
            $table->integer('section_id');
            $table->integer('teacher_id');
            $table->string('notice_type', 10);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('academic_yr', 11);
            $table->char('publish', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notice');
    }
};

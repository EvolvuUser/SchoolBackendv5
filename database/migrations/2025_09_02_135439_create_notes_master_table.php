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
        Schema::create('notes_master', function (Blueprint $table) {
            $table->integer('notes_id', true);
            $table->date('date');
            $table->date('publish_date')->nullable();
            $table->integer('class_id');
            $table->integer('teacher_id');
            $table->integer('section_id');
            $table->integer('subject_id');
            $table->string('description', 500)->default('');
            $table->string('academic_yr', 11);
            $table->char('publish', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes_master');
    }
};

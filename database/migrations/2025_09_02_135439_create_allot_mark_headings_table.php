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
        Schema::create('allot_mark_headings', function (Blueprint $table) {
            $table->integer('allot_markheadings_id', true);
            $table->integer('class_id');
            $table->integer('exam_id');
            $table->integer('sm_id');
            $table->integer('marks_headings_id');
            $table->string('academic_yr', 11);
            $table->integer('highest_marks');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['class_id', 'exam_id', 'sm_id', 'marks_headings_id'], 'class_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allot_mark_headings');
    }
};

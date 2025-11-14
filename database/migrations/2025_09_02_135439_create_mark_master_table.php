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
        Schema::create('mark_master', function (Blueprint $table) {
            $table->integer('mark_id', true);
            $table->integer('class_id')->index('class_id');
            $table->integer('section_id')->index('section_id');
            $table->integer('exam_id')->index('exam_id');
            $table->string('subject_id', 100)->index('subject_id');
            $table->integer('marks_headings_id');
            $table->date('date');
            $table->string('academic_yr', 11);

            $table->unique(['class_id', 'section_id', 'exam_id', 'subject_id', 'marks_headings_id'], 'class_id_2');
            $table->index(['subject_id'], 'subject_id_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mark_master');
    }
};

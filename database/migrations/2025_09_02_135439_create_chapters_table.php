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
        Schema::create('chapters', function (Blueprint $table) {
            $table->integer('chapter_id', true);
            $table->integer('class_id');
            $table->integer('subject_id');
            $table->integer('chapter_no');
            $table->string('name', 100);
            $table->string('sub_subject', 50);
            $table->string('description', 500);
            $table->string('difficulty_level', 10);
            $table->char('isDelete', 1);
            $table->char('publish', 1);
            $table->integer('created_by');
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chapters');
    }
};

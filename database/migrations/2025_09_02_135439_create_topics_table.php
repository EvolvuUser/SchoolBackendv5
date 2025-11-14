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
        Schema::create('topics', function (Blueprint $table) {
            $table->integer('topic_id', true);
            $table->integer('chapter_id');
            $table->string('name', 100);
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
        Schema::dropIfExists('topics');
    }
};

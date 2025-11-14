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
        Schema::create('allocation_parent_to_student', function (Blueprint $table) {
            $table->integer('parent_id');
            $table->integer('student_id');
            $table->integer('section_id');
            $table->integer('class_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allocation_parent_to_student');
    }
};

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
        Schema::create('fees_student_category', function (Blueprint $table) {
            $table->integer('fees_category_id');
            $table->integer('student_id');
            $table->string('academic_yr', 11);

            $table->unique(['fees_category_id', 'student_id', 'academic_yr'], 'fees_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees_student_category');
    }
};

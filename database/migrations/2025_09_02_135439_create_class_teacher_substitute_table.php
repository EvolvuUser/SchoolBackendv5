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
        Schema::create('class_teacher_substitute', function (Blueprint $table) {
            $table->integer('class_substitute_id', true);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('class_teacher_id');
            $table->integer('teacher_id');
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_teacher_substitute');
    }
};

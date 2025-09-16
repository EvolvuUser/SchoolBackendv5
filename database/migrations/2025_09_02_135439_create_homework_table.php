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
        Schema::create('homework', function (Blueprint $table) {
            $table->integer('homework_id', true);
            $table->string('description', 500)->default('');
            $table->integer('teacher_id');
            $table->integer('section_id')->index('section_id');
            $table->integer('sm_id');
            $table->integer('class_id')->index('class_id');
            $table->dateTime('end_date');
            $table->dateTime('start_date');
            $table->date('publish_date')->nullable();
            $table->string('academic_yr', 11);
            $table->char('publish', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homework');
    }
};

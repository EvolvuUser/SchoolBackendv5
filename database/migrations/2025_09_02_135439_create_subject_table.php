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
        Schema::create('subject', function (Blueprint $table) {
            $table->integer('subject_id', true);
            $table->integer('sm_id')->index('indx_sm_id');
            $table->integer('class_id')->index('class_id');
            $table->integer('section_id')->index('section_id');
            $table->integer('teacher_id')->nullable()->index('teacher_id');
            $table->string('academic_yr', 11);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject');
    }
};

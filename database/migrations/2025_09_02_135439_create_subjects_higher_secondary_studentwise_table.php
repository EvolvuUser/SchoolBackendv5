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
        Schema::create('subjects_higher_secondary_studentwise', function (Blueprint $table) {
            $table->integer('subject_hsc_id', true);
            $table->integer('student_id');
            $table->integer('sub_group_id');
            $table->integer('opt_subject_id');
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects_higher_secondary_studentwise');
    }
};

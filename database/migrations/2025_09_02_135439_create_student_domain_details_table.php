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
        Schema::create('student_domain_details', function (Blueprint $table) {
            $table->integer('sdd_id', true);
            $table->integer('student_id');
            $table->integer('class_id');
            $table->integer('section_id');
            $table->integer('term_id');
            $table->integer('dm_id');
            $table->integer('parameter_id');
            $table->string('parameter_value', 20)->nullable();
            $table->date('date');
            $table->integer('data_entry_by');
            $table->string('academic_yr', 9);
            $table->char('publish', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_domain_details');
    }
};

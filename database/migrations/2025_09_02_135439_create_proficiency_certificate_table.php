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
        Schema::create('proficiency_certificate', function (Blueprint $table) {
            $table->integer('pc_id', true);
            $table->integer('student_id');
            $table->string('type', 6);
            $table->char('publish', 1);
            $table->integer('created_by');
            $table->integer('term_id');
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proficiency_certificate');
    }
};

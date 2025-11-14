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
        Schema::create('ebsb_certificates', function (Blueprint $table) {
            $table->integer('e_certificate_id', true);
            $table->date('date');
            $table->string('class_id', 10);
            $table->integer('section_id');
            $table->integer('student_id');
            $table->char('publish', 1);
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebsb_certificates');
    }
};

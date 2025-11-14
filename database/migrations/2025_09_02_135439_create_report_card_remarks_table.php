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
        Schema::create('report_card_remarks', function (Blueprint $table) {
            $table->integer('report_card_remark_id', true);
            $table->integer('student_id');
            $table->integer('term_id');
            $table->string('remark', 500);
            $table->string('promot', 40)->nullable();
            $table->string('academic_yr', 11);

            $table->unique(['student_id', 'term_id'], 'student_id');
            $table->unique(['student_id', 'term_id', 'academic_yr'], 'student_id_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_card_remarks');
    }
};

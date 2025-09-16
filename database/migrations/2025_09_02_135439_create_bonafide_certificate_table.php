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
        Schema::create('bonafide_certificate', function (Blueprint $table) {
            $table->integer('sr_no', true);
            $table->integer('reg_no')->nullable();
            $table->string('stud_name', 200);
            $table->string('father_name', 50);
            $table->string('class_division', 10);
            $table->string('caste', 20)->nullable();
            $table->string('religion', 20)->nullable();
            $table->string('birth_place', 20)->nullable();
            $table->date('dob');
            $table->string('dob_words', 100);
            $table->string('purpose', 50)->nullable();
            $table->string('nationality', 50)->nullable();
            $table->integer('stud_id');
            $table->date('issue_date_bonafide');
            $table->string('academic_yr', 9);
            $table->char('IsGenerated', 1);
            $table->char('IsDeleted', 1)->nullable()->default('N');
            $table->char('IsIssued', 1)->nullable()->default('N');
            $table->date('issued_date')->nullable();
            $table->date('deleted_date')->nullable();
            $table->integer('generated_by');
            $table->integer('issued_by')->nullable();
            $table->integer('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bonafide_certificate');
    }
};

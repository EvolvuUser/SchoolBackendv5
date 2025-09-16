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
        Schema::create('percentage_certificate', function (Blueprint $table) {
            $table->integer('sr_no', true);
            $table->string('stud_name', 200);
            $table->string('class_division', 10);
            $table->string('roll_no', 10);
            $table->string('percentage', 100);
            $table->string('total', 100)->nullable();
            $table->integer('stud_id');
            $table->date('certi_issue_date');
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
        Schema::dropIfExists('percentage_certificate');
    }
};

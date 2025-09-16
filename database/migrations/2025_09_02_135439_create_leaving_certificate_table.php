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
        Schema::create('leaving_certificate', function (Blueprint $table) {
            $table->integer('sr_no', true);
            $table->integer('grn_no');
            $table->date('issue_date');
            $table->string('stud_id_no', 25)->nullable();
            $table->string('aadhar_no', 15)->nullable();
            $table->string('stud_name', 300);
            $table->string('mid_name', 30)->nullable();
            $table->string('last_name', 30)->nullable();
            $table->string('father_name', 100);
            $table->string('mother_name', 100);
            $table->string('nationality', 50);
            $table->string('mother_tongue', 20);
            $table->string('religion', 100);
            $table->string('caste', 100)->nullable();
            $table->string('subcaste', 100)->nullable();
            $table->string('birth_place', 50);
            $table->string('taluka', 50)->nullable();
            $table->string('district', 50)->nullable();
            $table->string('state', 50)->nullable();
            $table->string('country', 50)->nullable();
            $table->date('dob');
            $table->string('dob_words', 100)->nullable();
            $table->string('last_school_attended_standard', 100);
            $table->date('date_of_admission');
            $table->string('admission_class', 10);
            $table->string('academic_progress', 100)->nullable();
            $table->string('conduct', 100);
            $table->date('leaving_date');
            $table->string('standard_studying', 20);
            $table->integer('since_when');
            $table->string('reason_leaving', 100);
            $table->string('remark', 100);
            $table->date('application_date')->nullable();
            $table->integer('stud_id');
            $table->string('academic_yr', 11);
            $table->integer('generated_by');
            $table->integer('issued_by');
            $table->integer('deleted_by');
            $table->date('issued_date');
            $table->date('deleted_date');
            $table->char('IsDelete', 1);
            $table->char('IsIssued', 1);
            $table->char('IsGenerated', 1);
            $table->string('cancel_reason', 200);
            $table->string('dob_proof', 100);
            $table->string('last_exam', 100);
            $table->string('subjects_studied', 150);
            $table->string('promoted_to', 10);
            $table->string('attendance', 7);
            $table->string('fee_month', 50);
            $table->string('part_of', 10);
            $table->string('games', 100)->nullable();
            $table->string('udise_pen_no', 11)->nullable();
            $table->string('apaar_id', 12)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaving_certificate');
    }
};

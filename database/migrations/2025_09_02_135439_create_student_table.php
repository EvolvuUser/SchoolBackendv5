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
        Schema::create('student', function (Blueprint $table) {
            $table->integer('student_id', true);
            $table->string('academic_yr', 11);
            $table->integer('parent_id');
            $table->string('first_name', 100);
            $table->string('mid_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('student_name', 100);
            $table->date('dob')->nullable();
            $table->char('gender', 1)->nullable();
            $table->date('admission_date')->nullable();
            $table->string('stud_id_no', 25)->nullable();
            $table->string('mother_tongue', 20)->nullable();
            $table->string('birth_place', 50)->nullable();
            $table->string('admission_class', 7)->nullable();
            $table->integer('roll_no')->nullable();
            $table->integer('class_id');
            $table->integer('section_id');
            $table->integer('fees_paid')->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->string('religion', 100)->nullable();
            $table->string('caste', 100)->nullable();
            $table->string('subcaste', 100)->nullable();
            $table->string('transport_mode', 100)->nullable();
            $table->string('vehicle_no', 13)->nullable();
            $table->integer('bus_id')->nullable();
            $table->string('emergency_name', 100)->nullable();
            $table->string('emergency_contact', 11)->nullable();
            $table->string('emergency_add', 200)->nullable();
            $table->decimal('height', 4, 1)->nullable();
            $table->decimal('weight', 4, 1)->nullable();
            $table->char('has_specs', 1)->nullable();
            $table->string('allergies', 200)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('permant_add', 200)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->integer('pincode')->nullable();
            $table->char('IsDelete', 1)->default('N');
            $table->integer('prev_year_student_id');
            $table->char('isPromoted', 1);
            $table->char('isNew', 1);
            $table->char('isModify', 1);
            $table->char('isActive', 1)->default('Y');
            $table->string('reg_no', 10)->nullable();
            $table->char('house', 1)->nullable();
            $table->string('stu_aadhaar_no', 14)->nullable();
            $table->string('category', 8)->nullable();
            $table->date('last_date');
            $table->string('slc_no', 10);
            $table->date('slc_issue_date');
            $table->string('leaving_remark', 100);
            $table->date('deleted_date')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->string('image_name', 100);
            $table->string('guardian_name', 100);
            $table->string('guardian_add', 200);
            $table->string('guardian_mobile', 13);
            $table->string('relation', 20);
            $table->string('guardian_image_name', 100);
            $table->string('udise_pen_no', 11)->nullable();
            $table->string('apaar_id', 12)->nullable();
            $table->date('added_bk_date')->nullable();
            $table->integer('added_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student');
    }
};

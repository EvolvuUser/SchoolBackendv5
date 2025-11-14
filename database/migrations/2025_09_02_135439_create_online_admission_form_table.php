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
        Schema::create('online_admission_form', function (Blueprint $table) {
            $table->integer('adm_form_pk', true)->index('adm_form_pk');
            $table->string('form_id', 20)->unique('form_id');
            $table->string('academic_yr', 11);
            $table->string('first_name', 100);
            $table->string('mid_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->date('dob')->nullable();
            $table->string('birth_place', 50)->nullable();
            $table->char('gender', 1)->nullable();
            $table->date('application_date');
            $table->string('religion', 100)->nullable();
            $table->string('caste', 100)->nullable();
            $table->string('subcaste', 100)->nullable();
            $table->string('nationality', 100)->nullable();
            $table->string('mother_tongue', 20);
            $table->string('category', 8);
            $table->string('locality', 50);
            $table->string('city', 30);
            $table->string('state', 30);
            $table->integer('pincode');
            $table->string('perm_address', 100);
            $table->char('sibling', 1);
            $table->string('sibling_class_id', 10)->nullable();
            $table->string('sibling_student_id', 100)->nullable();
            $table->string('father_name', 100)->nullable();
            $table->string('father_occupation', 100)->nullable();
            $table->string('f_mobile', 10)->nullable();
            $table->string('f_email', 50)->nullable();
            $table->string('mother_occupation', 100)->nullable();
            $table->string('mother_name', 100)->nullable();
            $table->string('m_mobile', 13)->nullable();
            $table->string('m_emailid', 50)->nullable();
            $table->string('f_aadhar_no', 14)->nullable();
            $table->string('area_in_which_parent_can_contribute', 100)->nullable();
            $table->string('other_area', 50);
            $table->string('blood_group', 5)->nullable();
            $table->string('current_school_class', 100)->nullable();
            $table->string('acheivements', 100)->nullable();
            $table->string('stud_aadhar', 14)->nullable();
            $table->string('m_qualification', 50)->nullable();
            $table->string('m_designation', 50)->nullable();
            $table->string('m_aadhar_no', 14)->nullable();
            $table->string('m_nature_of_bussiness', 100)->nullable();
            $table->string('m_office_add', 100)->nullable();
            $table->string('f_qualification', 50)->nullable();
            $table->string('f_designation', 50)->nullable();
            $table->string('f_nature_of_bussiness', 100)->nullable();
            $table->string('f_office_add', 100)->nullable();
            $table->char('status', 1)->nullable();
            $table->string('admission_form_status', 20)->nullable();
            $table->string('sms_sending_phone_no', 10);
            $table->integer('class_id');
            $table->integer('student_id');
            $table->integer('nar_id');

            $table->primary(['adm_form_pk']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_admission_form');
    }
};

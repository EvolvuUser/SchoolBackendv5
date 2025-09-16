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
        Schema::create('student_old', function (Blueprint $table) {
            $table->integer('student_id', true);
            $table->string('first_name', 100);
            $table->string('mid_name', 100);
            $table->string('last_name', 100);
            $table->string('home_add', 200);
            $table->integer('home_tel');
            $table->string('aadhar_card', 12);
            $table->string('caste', 100);
            $table->string('subcaste', 100);
            $table->string('transport_name', 100);
            $table->integer('transport_contact');
            $table->string('transport_bus_no', 4);
            $table->date('dob');
            $table->date('admission_date');
            $table->char('gender', 1);
            $table->string('religion', 50);
            $table->string('blood_group', 4);
            $table->string('nationality', 100);
            $table->string('phone', 12);
            $table->string('email', 30);
            $table->string('father_name', 100);
            $table->string('mother_name', 100);
            $table->integer('class_id')->index('class_id');
            $table->integer('section_id')->index('section_id');
            $table->integer('roll_no');
            $table->string('emergency_name', 100);
            $table->string('emergency_address', 200);
            $table->string('emergency_contact', 13);
            $table->string('allergies', 200);
            $table->string('permant_add', 200);
            $table->string('state', 100);
            $table->string('city', 100);
            $table->integer('pincode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_old');
    }
};

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
        Schema::create('teacher', function (Blueprint $table) {
            $table->integer('teacher_id', true);
            $table->string('employee_id', 5);
            $table->string('name', 100);
            $table->string('father_spouse_name', 100);
            $table->date('birthday')->nullable();
            $table->date('date_of_joining')->nullable();
            $table->string('sex', 6);
            $table->string('religion', 50)->nullable();
            $table->string('blood_group', 4)->nullable();
            $table->string('address', 200)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 50)->nullable();
            $table->string('designation', 50)->nullable();
            $table->string('academic_qual', 100)->nullable();
            $table->string('professional_qual', 10)->nullable();
            $table->string('special_sub', 20)->nullable();
            $table->string('trained', 11);
            $table->integer('experience');
            $table->string('aadhar_card_no', 14)->nullable();
            $table->string('teacher_image_name', 100)->nullable();
            $table->integer('class_id')->nullable();
            $table->integer('section_id')->nullable();
            $table->char('tc_id', 2);
            $table->char('isDelete', 1);
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher');
    }
};

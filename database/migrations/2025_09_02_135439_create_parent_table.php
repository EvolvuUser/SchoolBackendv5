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
        Schema::create('parent', function (Blueprint $table) {
            $table->integer('parent_id', true);
            $table->string('father_name', 100)->nullable();
            $table->string('father_occupation', 100)->nullable();
            $table->string('f_office_add', 200)->nullable();
            $table->string('f_office_tel', 11)->nullable();
            $table->string('f_mobile', 10)->nullable();
            $table->string('f_email', 50)->nullable();
            $table->string('mother_occupation', 100)->nullable();
            $table->string('m_office_add', 200)->nullable();
            $table->string('m_office_tel', 11)->nullable();
            $table->string('mother_name', 100)->nullable();
            $table->string('m_mobile', 13)->nullable();
            $table->string('m_emailid', 50)->nullable();
            $table->string('parent_adhar_no', 14)->nullable();
            $table->string('m_adhar_no', 14);
            $table->date('f_dob')->nullable();
            $table->date('m_dob')->nullable();
            $table->string('f_blood_group', 5)->nullable();
            $table->string('m_blood_group', 5)->nullable();
            $table->string('f_qualification', 50)->nullable();
            $table->string('m_qualification', 50)->nullable();
            $table->char('IsDelete', 1)->default('N');
            $table->string('father_image_name', 100)->nullable();
            $table->string('mother_image_name', 100)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent');
    }
};

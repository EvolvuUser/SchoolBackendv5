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
        Schema::create('remark', function (Blueprint $table) {
            $table->integer('remark_id', true);
            $table->string('remark_desc', 350);
            $table->string('remark_subject', 100);
            $table->string('remark_type', 15)->nullable();
            $table->dateTime('remark_date');
            $table->date('publish_date')->nullable();
            $table->integer('class_id');
            $table->integer('section_id');
            $table->integer('student_id');
            $table->integer('subject_id')->nullable();
            $table->integer('teacher_id');
            $table->string('academic_yr', 11);
            $table->char('publish', 1);
            $table->char('acknowledge', 1)->default('N');
            $table->char('isDelete', 1)->default('N');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remark');
    }
};

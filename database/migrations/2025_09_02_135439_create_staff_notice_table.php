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
        Schema::create('staff_notice', function (Blueprint $table) {
            $table->integer('t_notice_id', true);
            $table->integer('unq_id');
            $table->string('subject', 100);
            $table->string('notice_desc', 1000);
            $table->date('notice_date');
            $table->integer('teacher_id');
            $table->string('notice_type', 10);
            $table->string('academic_yr', 11);
            $table->char('publish', 1);
            $table->integer('created_by');
            $table->string('department_id', 4);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_notice');
    }
};

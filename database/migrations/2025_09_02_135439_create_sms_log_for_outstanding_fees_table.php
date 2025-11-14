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
        Schema::create('sms_log_for_outstanding_fees', function (Blueprint $table) {
            $table->integer('sms_log_id', true);
            $table->integer('student_id');
            $table->integer('parent_id');
            $table->integer('installment');
            $table->string('phone_no', 13);
            $table->integer('count_of_sms');
            $table->dateTime('date_last_sms_sent');
            $table->string('academic_yr', 11);

            $table->unique(['student_id', 'parent_id', 'installment', 'academic_yr'], 'student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_log_for_outstanding_fees');
    }
};

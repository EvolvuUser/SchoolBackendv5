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
        Schema::create('staff_notice_sms_log', function (Blueprint $table) {
            $table->integer('t_notice_sms_log_id', true);
            $table->integer('teacher_id');
            $table->integer('notice_id');
            $table->string('phone_no', 13);
            $table->date('sms_date');
            $table->string('sms_status', 400)->nullable();
            $table->char('sms_sent', 1)->default('N');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_notice_sms_log');
    }
};

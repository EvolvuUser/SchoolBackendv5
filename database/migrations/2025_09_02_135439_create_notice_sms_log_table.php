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
        Schema::create('notice_sms_log', function (Blueprint $table) {
            $table->integer('notice_sms_log_id', true);
            $table->integer('stu_teacher_id');
            $table->integer('notice_id');
            $table->string('phone_no', 13)->nullable();
            $table->date('sms_date');
            $table->string('sms_status', 400);
            $table->char('sms_sent', 1)->default('N');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notice_sms_log');
    }
};

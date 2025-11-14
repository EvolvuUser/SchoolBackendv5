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
        Schema::create('redington_webhook_details', function (Blueprint $table) {
            $table->integer('webhook_id', true);
            $table->string('wa_id', 100);
            $table->string('phone_no', 12)->nullable();
            $table->string('message_type', 40)->nullable();
            $table->string('status', 10)->nullable();
            $table->string('sms_sent', 1)->default('N');
            $table->integer('stu_teacher_id')->nullable();
            $table->integer('notice_id')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redington_webhook_details');
    }
};

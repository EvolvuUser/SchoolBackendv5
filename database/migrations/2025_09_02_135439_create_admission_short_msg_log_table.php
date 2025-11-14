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
        Schema::create('admission_short_msg_log', function (Blueprint $table) {
            $table->integer('short_msg_id');
            $table->date('sms_date');
            $table->string('phone_no', 13);
            $table->string('sms_status', 200);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_short_msg_log');
    }
};

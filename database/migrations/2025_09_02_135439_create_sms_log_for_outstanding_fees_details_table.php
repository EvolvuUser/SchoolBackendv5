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
        Schema::create('sms_log_for_outstanding_fees_details', function (Blueprint $table) {
            $table->integer('sms_log_id')->index('fk_sms_log_id');
            $table->date('date_sms_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_log_for_outstanding_fees_details');
    }
};

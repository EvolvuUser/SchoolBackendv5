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
        Schema::create('icicipg_payment_details', function (Blueprint $table) {
            $table->string('OrderId', 30)->primary();
            $table->string('txnID', 24);
            $table->string('Account_type', 20)->nullable();
            $table->decimal('Amount', 13);
            $table->decimal('IP_Amount', 13)->nullable();
            $table->dateTime('Trnx_date');
            $table->string('payment_mode', 20)->nullable();
            $table->string('responseCode', 6)->nullable();
            $table->string('response_desc', 45)->nullable();
            $table->char('status_code', 1)->nullable();
            $table->string('payment_details_json', 3000);
            $table->string('student_name', 500);
            $table->string('parent_name', 200);
            $table->string('installment_no', 200);
            $table->string('class_name', 100);
            $table->integer('reg_id');
            $table->string('academic_yr', 11);
            $table->char('synced_later', 1)->nullable();
            $table->date('sync_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('icicipg_payment_details');
    }
};

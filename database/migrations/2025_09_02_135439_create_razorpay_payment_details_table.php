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
        Schema::create('razorpay_payment_details', function (Blueprint $table) {
            $table->string('OrderId', 30)->primary();
            $table->string('razorpay_payment_id', 50);
            $table->string('razorpay_signature', 100);
            $table->string('RRN', 30)->nullable();
            $table->string('Account_type', 20)->nullable();
            $table->decimal('Amount', 13);
            $table->decimal('RP_amount', 13)->nullable();
            $table->dateTime('Trnx_date');
            $table->char('Status', 1);
            $table->string('payment_details_json', 3000);
            $table->string('student_name', 500);
            $table->string('parent_name', 200);
            $table->string('installment_no', 200);
            $table->string('class_name', 100);
            $table->integer('reg_id');
            $table->string('academic_yr', 11);
            $table->char('synced_later', 1)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('razorpay_payment_details');
    }
};

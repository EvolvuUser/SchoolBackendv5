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
        Schema::create('onlinefees_payment_attempts', function (Blueprint $table) {
            $table->integer('txnid', true);
            $table->integer('parent_id');
            $table->integer('student_id');
            $table->decimal('amount', 9);
            $table->dateTime('date');
            $table->string('phone', 10);
            $table->string('email', 50);
            $table->string('notes', 100);
            $table->char('status', 1);
            $table->string('OrderId', 30);
            $table->string('qfRefNumber', 20);
            $table->string('razorpayPaymentId', 30);
            $table->integer('fees_payment_id');
            $table->string('academic_yr', 11);
            $table->string('app_web', 3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onlinefees_payment_attempts');
    }
};

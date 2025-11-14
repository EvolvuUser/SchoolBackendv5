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
        Schema::create('onlinefees_payment_record', function (Blueprint $table) {
            $table->integer('fees_payment_id')->primary();
            $table->integer('student_id')->index('idx_opayment_stu');
            $table->date('payment_date');
            $table->decimal('amount', 10);
            $table->decimal('concession', 10);
            $table->decimal('payment_amount', 10);
            $table->string('payment_mode', 10);
            $table->string('cheque_no', 50);
            $table->string('bank_name', 50);
            $table->integer('fee_allotment_id');
            $table->string('receipt_no', 13);
            $table->integer('dataentry_by');
            $table->char('isCancel', 1);
            $table->string('cancel_reason', 500);
            $table->char('cheque_bounce', 1)->default('N');
            $table->string('academic_yr', 11)->index('idx_opayment_acdyr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onlinefees_payment_record');
    }
};

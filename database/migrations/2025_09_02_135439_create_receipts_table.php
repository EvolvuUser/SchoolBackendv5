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
        Schema::create('receipts', function (Blueprint $table) {
            $table->integer('receipt_id', true);
            $table->string('received_from', 100);
            $table->integer('income_id')->index('income_id');
            $table->dateTime('receipt_date');
            $table->double('amount');
            $table->char('receipt_mode', 1);
            $table->integer('cheque_no');
            $table->string('comment', 500);
            $table->integer('staff_id')->index('staff_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};

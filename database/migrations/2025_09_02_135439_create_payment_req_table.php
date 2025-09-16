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
        Schema::create('payment_req', function (Blueprint $table) {
            $table->integer('payment_id', true);
            $table->integer('payee_id')->index('payee_id');
            $table->date('pay_req_date');
            $table->integer('expense_id')->index('expense_id');
            $table->date('due_date');
            $table->decimal('amount', 10, 0);
            $table->string('comments', 500);
            $table->integer('staff_id')->index('staff_id');
            $table->char('status', 1);
            $table->string('reason_for_rejection', 500);
            $table->integer('approved_by')->nullable()->index('approved_by');
            $table->dateTime('approval_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_req');
    }
};

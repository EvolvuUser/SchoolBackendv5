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
        Schema::create('online_admfee', function (Blueprint $table) {
            $table->integer('adfees_payment_id', true);
            $table->string('OrderId', 30);
            $table->char('status', 1);
            $table->string('form_id', 20);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('parent_name', 100);
            $table->string('phone', 12);
            $table->string('email', 50);
            $table->string('remark', 200);
            $table->date('payment_date');
            $table->decimal('amount', 10);
            $table->integer('Trnx_ref_no');
            $table->integer('rrn')->nullable();
            $table->char('Status_code', 1);
            $table->string('Status_desc', 100);
            $table->char('synced_later', 1);
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_admfee');
    }
};

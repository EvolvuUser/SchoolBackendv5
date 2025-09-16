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
        Schema::create('rp_dummy_table', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('orderId', 40);
            $table->string('status', 15);
            $table->string('razorpay_payment_id', 50);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rp_dummy_table');
    }
};

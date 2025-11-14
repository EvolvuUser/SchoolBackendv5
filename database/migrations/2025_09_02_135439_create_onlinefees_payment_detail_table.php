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
        Schema::create('onlinefees_payment_detail', function (Blueprint $table) {
            $table->integer('fees_payment_id');
            $table->integer('installment');
            $table->integer('fee_type_id');
            $table->decimal('amount');
            $table->string('academic_yr', 11);

            $table->unique(['fees_payment_id', 'installment', 'fee_type_id'], 'fees_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onlinefees_payment_detail');
    }
};

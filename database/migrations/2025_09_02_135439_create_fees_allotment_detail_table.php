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
        Schema::create('fees_allotment_detail', function (Blueprint $table) {
            $table->integer('fee_allotment_id');
            $table->integer('installment');
            $table->date('due_date');
            $table->decimal('installment_fees', 10);
            $table->integer('fee_type_id')->index('fk_allot_feetype');
            $table->decimal('amount');
            $table->string('academic_yr', 11);

            $table->unique(['fee_allotment_id', 'installment', 'fee_type_id', 'academic_yr'], 'inx_fad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees_allotment_detail');
    }
};

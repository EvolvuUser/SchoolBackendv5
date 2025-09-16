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
        Schema::create('fee_concession_details', function (Blueprint $table) {
            $table->integer('fee_concession_id', true);
            $table->integer('student_id');
            $table->integer('installment');
            $table->integer('fee_type_id');
            $table->decimal('amount');
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_concession_details');
    }
};

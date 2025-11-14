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
        Schema::create('classwise_period_allocation', function (Blueprint $table) {
            $table->integer('c_p_id', true);
            $table->integer('class_id');
            $table->integer('section_id');
            $table->integer('mon-fri');
            $table->integer('sat');
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classwise_period_allocation');
    }
};

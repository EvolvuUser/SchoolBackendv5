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
        Schema::create('teachers_period_allocation', function (Blueprint $table) {
            $table->integer('t_p_id', true);
            $table->integer('teacher_id');
            $table->integer('periods_allocated');
            $table->integer('periods_used')->default(0);
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers_period_allocation');
    }
};

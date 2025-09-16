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
        Schema::create('late_attendance_count', function (Blueprint $table) {
            $table->integer('lac_id', true);
            $table->integer('employee_id');
            $table->integer('month');
            $table->integer('late_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('late_attendance_count');
    }
};

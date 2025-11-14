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
        Schema::create('holidaylist', function (Blueprint $table) {
            $table->integer('holiday_id', true);
            $table->string('title', 100);
            $table->date('holiday_date');
            $table->date('to_date')->nullable();
            $table->char('isDelete', 1);
            $table->char('publish', 1);
            $table->integer('created_by');
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidaylist');
    }
};

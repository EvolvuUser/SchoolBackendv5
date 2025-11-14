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
        Schema::create('tmptrecords', function (Blueprint $table) {
            $table->integer('serial_no')->nullable();
            $table->integer('clock_id')->nullable();
            $table->text('card_id')->nullable();
            $table->text('emp_id')->nullable();
            $table->date('KqDate')->nullable();
            $table->time('KqTime')->nullable();
            $table->integer('mark')->nullable();
            $table->integer('flag')->nullable();
            $table->text('sign_cause')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmptrecords');
    }
};

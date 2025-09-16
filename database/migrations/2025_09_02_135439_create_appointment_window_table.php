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
        Schema::create('appointment_window', function (Blueprint $table) {
            $table->integer('aw_id', true);
            $table->char('role_id', 1);
            $table->integer('class_id');
            $table->string('week', 10);
            $table->string('weekday', 100);
            $table->time('time_from');
            $table->time('time_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_window');
    }
};

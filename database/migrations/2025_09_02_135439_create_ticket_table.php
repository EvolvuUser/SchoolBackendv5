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
        Schema::create('ticket', function (Blueprint $table) {
            $table->integer('ticket_id', true);
            $table->string('title', 100);
            $table->string('description', 1000);
            $table->integer('student_id');
            $table->integer('service_id');
            $table->integer('created_by');
            $table->dateTime('raised_on');
            $table->string('status', 20);
            $table->string('document', 100)->nullable();
            $table->string('acd_yr', 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket');
    }
};

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
        Schema::create('bus_allocation_stu_wise', function (Blueprint $table) {
            $table->integer('bus_allocation_id');
            $table->integer('student_id');
            $table->integer('parent_id');
            $table->integer('bus_id');
            $table->string('status', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_allocation_stu_wise');
    }
};

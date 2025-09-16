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
        Schema::create('buses', function (Blueprint $table) {
            $table->integer('bus_id')->primary();
            $table->string('bus_name', 30)->unique('bus_name');
            $table->string('bus_reg_no', 10)->unique('bus_no');
            $table->string('driver_name', 30);
            $table->string('driver_no', 12);

            $table->unique(['bus_reg_no'], 'bus_no_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buses');
    }
};

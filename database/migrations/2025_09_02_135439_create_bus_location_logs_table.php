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
        Schema::create('bus_location_logs', function (Blueprint $table) {
            $table->integer('bus_location_log_key')->primary();
            $table->integer('reg_id');
            $table->integer('route_id');
            $table->decimal('latitude', 11, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('speed', 50);
            $table->string('time', 50);
            $table->timestamp('add_dt')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bus_location_logs');
    }
};

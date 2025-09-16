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
        Schema::create('route_master', function (Blueprint $table) {
            $table->integer('route_id', true);
            $table->string('route_name', 50);
            $table->integer('bus_id');
            $table->string('route_type', 6);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_master');
    }
};

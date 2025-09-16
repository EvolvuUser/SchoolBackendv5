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
        Schema::create('service_type', function (Blueprint $table) {
            $table->integer('service_id', true);
            $table->string('service_name', 30);
            $table->char('role_id', 1);
            $table->string('description', 200)->nullable();
            $table->char('RequiresAppointment', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_type');
    }
};

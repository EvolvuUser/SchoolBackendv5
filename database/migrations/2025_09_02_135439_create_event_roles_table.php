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
        Schema::create('event_roles', function (Blueprint $table) {
            $table->string('role_id', 2);
            $table->string('name', 20)->unique('name');
            $table->string('is_active', 1);

            $table->unique(['role_id', 'name'], 'role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_roles');
    }
};

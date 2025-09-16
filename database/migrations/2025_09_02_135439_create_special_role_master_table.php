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
        Schema::create('special_role_master', function (Blueprint $table) {
            $table->char('sp_role_id', 2)->primary();
            $table->string('name', 30);
            $table->char('is_active', 1)->nullable()->default('Y');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['sp_role_id'], 'sp_role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_role_master');
    }
};

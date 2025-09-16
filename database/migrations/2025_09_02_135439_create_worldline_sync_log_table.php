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
        Schema::create('worldline_sync_log', function (Blueprint $table) {
            $table->integer('WL_log_id', true);
            $table->string('OrderId', 30);
            $table->dateTime('sync_date')->useCurrent();
            $table->char('status_code', 1)->nullable();
            $table->string('response', 2000);
            $table->string('updated_from', 10)->nullable();
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worldline_sync_log');
    }
};

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
        Schema::create('subscription_volume', function (Blueprint $table) {
            $table->integer('subscription_vol_id');
            $table->integer('subscription_id');
            $table->string('volume', 10);
            $table->integer('no_of_issues');
            $table->date('volume_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_volume');
    }
};

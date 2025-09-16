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
        Schema::create('login_record', function (Blueprint $table) {
            $table->string('user_id', 50);
            $table->integer('login_count');
            $table->string('academic_yr', 11);
            $table->dateTime('last_login_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_record');
    }
};

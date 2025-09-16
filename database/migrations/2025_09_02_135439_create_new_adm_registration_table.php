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
        Schema::create('new_adm_registration', function (Blueprint $table) {
            $table->integer('nar_id', true);
            $table->string('parent_name', 100);
            $table->string('email', 100);
            $table->string('phone_no', 10);
            $table->date('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_adm_registration');
    }
};

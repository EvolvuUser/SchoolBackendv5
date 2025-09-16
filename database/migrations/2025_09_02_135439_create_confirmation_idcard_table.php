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
        Schema::create('confirmation_idcard', function (Blueprint $table) {
            $table->integer('confirmation_idcard_id', true);
            $table->integer('parent_id');
            $table->string('academic_yr', 11);
            $table->char('confirm', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('confirmation_idcard');
    }
};

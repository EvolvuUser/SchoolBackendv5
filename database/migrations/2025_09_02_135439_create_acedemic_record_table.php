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
        Schema::create('acedemic_record', function (Blueprint $table) {
            $table->integer('acedemic_id');
            $table->integer('ad_id');
            $table->string('exam', 100);
            $table->string('year', 15);
            $table->string('institution', 100);
            $table->string('university', 100);
            $table->string('subject', 100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acedemic_record');
    }
};

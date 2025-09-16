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
        Schema::create('domain_master', function (Blueprint $table) {
            $table->integer('dm_id', true);
            $table->integer('class_id');
            $table->integer('HPC_sm_id');
            $table->string('name', 100);
            $table->string('curriculum_goal', 200);
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_master');
    }
};

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
        Schema::create('HPC_subject_master', function (Blueprint $table) {
            $table->integer('hpc_sm_id', true);
            $table->string('name', 50);
            $table->string('subject_type', 20);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('HPC_subject_master');
    }
};

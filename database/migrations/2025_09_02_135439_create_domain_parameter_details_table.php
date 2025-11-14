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
        Schema::create('domain_parameter_details', function (Blueprint $table) {
            $table->integer('parameter_id', true);
            $table->integer('dm_id');
            $table->string('competencies', 30);
            $table->string('learning_outcomes', 200);
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domain_parameter_details');
    }
};

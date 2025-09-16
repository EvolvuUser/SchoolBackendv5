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
        Schema::create('new_admission_class', function (Blueprint $table) {
            $table->integer('nac_id', true);
            $table->integer('class_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('academic_yr', 9);
            $table->char('publish', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_admission_class');
    }
};

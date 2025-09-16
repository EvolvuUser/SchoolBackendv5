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
        Schema::create('lesson_plan_template_details', function (Blueprint $table) {
            $table->integer('les_pln_tempdetails_id', true);
            $table->integer('les_pln_temp_id');
            $table->integer('lesson_plan_headings_id');
            $table->string('description', 1000);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_template_details');
    }
};

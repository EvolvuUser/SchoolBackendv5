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
        Schema::create('visitors', function (Blueprint $table) {
            $table->integer('visitor_id', true);
            $table->integer('parent_id');
            $table->string('academic_yr', 11);
            $table->string('visit_by', 50);
            $table->date('visit_date');
            $table->time('visit_in_time');
            $table->time('visit_out_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};

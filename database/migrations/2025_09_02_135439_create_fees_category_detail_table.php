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
        Schema::create('fees_category_detail', function (Blueprint $table) {
            $table->integer('fees_category_id');
            $table->string('class_concession', 4);
            $table->string('academic_yr', 11);

            $table->unique(['fees_category_id', 'class_concession'], 'fees_category_id');
            $table->index(['fees_category_id', 'academic_yr'], 'id_acdyr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees_category_detail');
    }
};

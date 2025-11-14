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
        Schema::create('fees_allotment', function (Blueprint $table) {
            $table->integer('fee_allotment_id', true);
            $table->integer('fees_category_id')->index('idx_catid');
            $table->decimal('fees', 12);
            $table->string('academic_yr', 11)->index('idx_acdyr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees_allotment');
    }
};

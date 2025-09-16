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
        Schema::create('health_activity_record_publish', function (Blueprint $table) {
            $table->integer('hac_id', true);
            $table->integer('class_id');
            $table->integer('section_id');
            $table->char('publish', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_activity_record_publish');
    }
};

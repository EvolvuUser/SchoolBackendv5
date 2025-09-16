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
        Schema::create('homework_detail', function (Blueprint $table) {
            $table->integer('homework_id');
            $table->string('image_name', 100);
            $table->decimal('file_size', 10);

            $table->unique(['homework_id', 'image_name'], 'homework_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homework_detail');
    }
};

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
        Schema::create('answer_question_image', function (Blueprint $table) {
            $table->integer('question_bank_id');
            $table->integer('question_id');
            $table->string('image_name', 200);
            $table->decimal('file_size', 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answer_question_image');
    }
};

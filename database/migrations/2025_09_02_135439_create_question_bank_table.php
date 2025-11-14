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
        Schema::create('question_bank', function (Blueprint $table) {
            $table->integer('qb_id', true);
            $table->integer('question_bank_id');
            $table->integer('question_id');
            $table->string('qb_name', 70);
            $table->string('question_type', 20);
            $table->string('question', 500);
            $table->integer('weightage');
            $table->string('status', 3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_bank');
    }
};

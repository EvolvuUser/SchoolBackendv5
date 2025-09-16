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
        Schema::create('option_bank', function (Blueprint $table) {
            $table->integer('option_bank_id', true);
            $table->integer('question_bank_id');
            $table->integer('question_id');
            $table->integer('optid');
            $table->string('options', 200);
            $table->string('answer', 100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_bank');
    }
};

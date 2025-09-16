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
        Schema::create('exam', function (Blueprint $table) {
            $table->integer('exam_id', true);
            $table->string('name', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('open_day');
            $table->integer('term_id');
            $table->string('comment', 500)->nullable();
            $table->string('academic_yr', 11);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam');
    }
};

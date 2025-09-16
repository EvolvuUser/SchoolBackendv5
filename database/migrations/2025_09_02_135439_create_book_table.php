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
        Schema::create('book', function (Blueprint $table) {
            $table->integer('book_id')->primary();
            $table->string('book_title', 100);
            $table->integer('category_id')->index('category_id');
            $table->string('author', 100);
            $table->string('publisher', 100);
            $table->integer('days_borrow');
            $table->string('location_of_book', 50);
            $table->char('issue_type', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book');
    }
};

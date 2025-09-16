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
        Schema::create('book_copies', function (Blueprint $table) {
            $table->integer('book_copies_id')->primary();
            $table->integer('book_id')->index('book_id');
            $table->string('copy_id', 8)->unique('copy_id');
            $table->string('bill_no', 10);
            $table->string('source_of_book', 50);
            $table->string('isbn', 20);
            $table->string('year', 4);
            $table->string('edition', 10);
            $table->integer('no_of_pages');
            $table->decimal('price', 7);
            $table->date('added_date');
            $table->char('status', 1);
            $table->char('IsNew', 1)->default('N');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_copies');
    }
};

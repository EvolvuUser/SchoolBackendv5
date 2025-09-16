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
        Schema::create('nonreturned_books_remark_log', function (Blueprint $table) {
            $table->integer('b_r_log_id', true);
            $table->integer('student_id');
            $table->integer('remark_id');
            $table->integer('book_id');
            $table->date('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nonreturned_books_remark_log');
    }
};

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
        Schema::create('issue_return', function (Blueprint $table) {
            $table->integer('member_id')->index('member_id');
            $table->char('member_type', 1);
            $table->integer('book_id')->index('book_id');
            $table->string('copy_id', 8)->index('copy_id');
            $table->date('issue_date');
            $table->date('due_date');
            $table->date('return_date')->default('0000-00-00');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_return');
    }
};

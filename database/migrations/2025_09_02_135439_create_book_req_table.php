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
        Schema::create('book_req', function (Blueprint $table) {
            $table->integer('book_req_id', true);
            $table->string('title', 100);
            $table->string('author', 100)->nullable();
            $table->string('publisher', 100)->nullable();
            $table->date('req_date');
            $table->integer('member_id')->index('member_id');
            $table->char('member_type', 1);
            $table->char('status', 1);
            $table->integer('approved_by')->nullable()->index('approved_by');
            $table->date('approved_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_req');
    }
};

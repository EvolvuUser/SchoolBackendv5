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
        Schema::create('book_pur_req', function (Blueprint $table) {
            $table->integer('book_req_id')->primary();
            $table->date('req_date');
            $table->integer('raised_by')->index('raised_by');
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
        Schema::dropIfExists('book_pur_req');
    }
};

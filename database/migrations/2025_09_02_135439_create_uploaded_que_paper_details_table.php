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
        Schema::create('uploaded_que_paper_details', function (Blueprint $table) {
            $table->integer('uploaded_qp_id')->primary();
            $table->integer('question_bank_id');
            $table->decimal('file_size', 10)->nullable();
            $table->string('image_name', 50);

            $table->index(['uploaded_qp_id'], 'qp_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploaded_que_paper_details');
    }
};

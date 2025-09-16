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
        Schema::create('report_card_publish', function (Blueprint $table) {
            $table->integer('rcp_id', true);
            $table->integer('class_id');
            $table->integer('section_id');
            $table->integer('term_id');
            $table->date('reopen_date')->nullable();
            $table->char('publish', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_card_publish');
    }
};

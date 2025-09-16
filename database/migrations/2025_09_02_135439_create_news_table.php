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
        Schema::create('news', function (Blueprint $table) {
            $table->integer('news_id', true);
            $table->string('title', 100);
            $table->string('description', 500);
            $table->date('date_posted');
            $table->date('active_till_date')->nullable();
            $table->integer('posted_by');
            $table->string('url', 100)->nullable();
            $table->string('image_name', 100)->nullable();
            $table->char('publish', 1);
            $table->char('isDelete', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};

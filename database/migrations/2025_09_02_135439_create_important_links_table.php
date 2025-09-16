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
        Schema::create('important_links', function (Blueprint $table) {
            $table->integer('link_id', true);
            $table->string('title', 50);
            $table->string('description', 100)->nullable();
            $table->date('create_date');
            $table->string('url', 100);
            $table->integer('posted_by');
            $table->char('publish', 1);
            $table->char('isDelete', 1);
            $table->string('type_link', 7);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('important_links');
    }
};

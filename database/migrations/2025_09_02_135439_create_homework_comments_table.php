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
        Schema::create('homework_comments', function (Blueprint $table) {
            $table->integer('comment_id', true);
            $table->integer('homework_id');
            $table->integer('student_id');
            $table->integer('parent_id');
            $table->string('homework_status', 20);
            $table->string('comment', 500);
            $table->string('parent_comment', 500);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homework_comments');
    }
};

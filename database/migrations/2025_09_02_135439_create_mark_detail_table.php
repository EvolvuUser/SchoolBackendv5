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
        Schema::create('mark_detail', function (Blueprint $table) {
            $table->integer('mark_id')->index('mark_id');
            $table->integer('student_id')->index('student_id');
            $table->char('present', 1)->default('Y');
            $table->integer('mark_obtained')->nullable();
            $table->integer('highest_marks');
            $table->string('comment', 500)->nullable();
            $table->string('grade', 4);
            $table->string('percent', 5);
            $table->char('show_principal', 1)->default('N');
            $table->char('show_parent', 1)->default('N');

            $table->index(['student_id'], 'student_id_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mark_detail');
    }
};

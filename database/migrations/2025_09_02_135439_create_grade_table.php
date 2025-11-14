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
        Schema::create('grade', function (Blueprint $table) {
            $table->integer('grade_id', true);
            $table->integer('class_id');
            $table->string('subject_type', 20);
            $table->string('name', 3);
            $table->decimal('grade_point_from', 2, 1);
            $table->decimal('grade_point_upto', 2, 1);
            $table->decimal('mark_from', 4, 1);
            $table->decimal('mark_upto', 4, 1);
            $table->longText('comment')->nullable();
            $table->string('academic_yr', 11);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['class_id', 'subject_type', 'name'], 'class_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade');
    }
};

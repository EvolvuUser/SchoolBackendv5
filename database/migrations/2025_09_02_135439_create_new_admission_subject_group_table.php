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
        Schema::create('new_admission_subject_group', function (Blueprint $table) {
            $table->integer('nas_id', true);
            $table->integer('sub_group_id');
            $table->char('enable', 1);
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_admission_subject_group');
    }
};

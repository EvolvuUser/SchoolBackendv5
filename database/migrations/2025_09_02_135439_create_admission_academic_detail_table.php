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
        Schema::create('admission_academic_detail', function (Blueprint $table) {
            $table->string('form_id', 20)->unique('form_id');
            $table->decimal('9-marks', 5);
            $table->decimal('10-preboard', 5);
            $table->decimal('10-final', 5);
            $table->string('board', 40);
            $table->integer('sub_group_id');
            $table->integer('opt_subject_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admission_academic_detail');
    }
};

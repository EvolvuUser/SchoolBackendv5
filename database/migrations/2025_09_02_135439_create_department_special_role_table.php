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
        Schema::create('department_special_role', function (Blueprint $table) {
            $table->integer('special_role_id', true);
            $table->integer('department_id');
            $table->integer('teacher_id');
            $table->string('role', 17);
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_special_role');
    }
};

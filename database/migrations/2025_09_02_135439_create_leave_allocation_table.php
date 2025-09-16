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
        Schema::create('leave_allocation', function (Blueprint $table) {
            $table->integer('staff_id')->index('staff_id');
            $table->integer('leave_type_id')->index('leave_type_id');
            $table->decimal('leaves_allocated', 4, 1);
            $table->decimal('leaves_availed', 4, 1)->default(0);
            $table->string('academic_yr', 11);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_allocation');
    }
};

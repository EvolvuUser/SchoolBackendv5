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
        Schema::create('drop_address', function (Blueprint $table) {
            $table->integer('dropadd_id', true);
            $table->string('drop_address', 200);
            $table->string('locality', 100);
            $table->string('city', 30);
            $table->string('state', 30);
            $table->string('pincode', 6);
            $table->decimal('longitude', 11, 8);
            $table->decimal('latitude', 11, 8);
            $table->integer('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drop_address');
    }
};

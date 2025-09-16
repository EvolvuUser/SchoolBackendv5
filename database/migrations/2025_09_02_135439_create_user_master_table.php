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
        Schema::create('user_master', function (Blueprint $table) {
            $table->string('user_id', 50)->primary();
            $table->string('name', 200);
            $table->string('password');
            $table->integer('reg_id')->index('reg_id');
            $table->char('role_id', 1)->index('role_id');
            $table->string('answer_one', 100);
            $table->string('answer_two', 100);
            $table->char('IsDelete', 1)->default('N');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_master');
    }
};

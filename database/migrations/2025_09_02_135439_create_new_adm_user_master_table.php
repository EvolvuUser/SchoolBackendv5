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
        Schema::create('new_adm_user_master', function (Blueprint $table) {
            $table->string('user_id', 100)->unique('user_id');
            $table->string('password', 50);
            $table->integer('nar_id');
            $table->char('IsDelete', 1);
            $table->char('IsVerify', 1);

            $table->unique(['user_id', 'IsDelete'], 'user_id_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('new_adm_user_master');
    }
};

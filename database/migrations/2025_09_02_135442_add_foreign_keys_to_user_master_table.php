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
        Schema::table('user_master', function (Blueprint $table) {
            $table->foreign(['role_id'], 'FK_role_user')->references(['role_id'])->on('role_master')->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_master', function (Blueprint $table) {
            $table->dropForeign('FK_role_user');
        });
    }
};

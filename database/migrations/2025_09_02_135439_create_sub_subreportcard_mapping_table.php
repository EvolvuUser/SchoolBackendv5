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
        Schema::create('sub_subreportcard_mapping', function (Blueprint $table) {
            $table->integer('sub_mapping');
            $table->integer('sm_id');
            $table->integer('sub_rc_master_id');

            $table->unique(['sm_id', 'sub_rc_master_id'], 'sm_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_subreportcard_mapping');
    }
};

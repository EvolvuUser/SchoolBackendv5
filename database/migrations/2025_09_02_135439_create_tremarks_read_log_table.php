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
        Schema::create('tremarks_read_log', function (Blueprint $table) {
            $table->integer('remark_r_log_id', true);
            $table->integer('t_remark_id');
            $table->integer('teachers_id');
            $table->date('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tremarks_read_log');
    }
};

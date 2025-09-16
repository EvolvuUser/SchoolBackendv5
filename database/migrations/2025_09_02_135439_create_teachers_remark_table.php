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
        Schema::create('teachers_remark', function (Blueprint $table) {
            $table->integer('t_remark_id', true);
            $table->integer('teachers_id');
            $table->string('remark_subject', 100);
            $table->string('remark_desc', 300);
            $table->string('remark_type', 15);
            $table->date('remark_date');
            $table->date('publish_date');
            $table->integer('dataentry_by');
            $table->char('publish', 1);
            $table->char('acknowledge', 1);
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers_remark');
    }
};

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
        Schema::create('events', function (Blueprint $table) {
            $table->integer('event_id', true);
            $table->integer('unq_id');
            $table->string('title', 100);
            $table->string('event_desc', 500);
            $table->integer('class_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('login_type', 15);
            $table->char('isDelete', 1);
            $table->char('publish', 1);
            $table->integer('created_by');
            $table->char('competition', 1);
            $table->char('activity', 1)->nullable();
            $table->char('notify', 1);
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

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
        Schema::create('notifications_log', function (Blueprint $table) {
            $table->integer('daily_notification_id');
            $table->integer('student_id');
            $table->integer('parent_id');
            $table->integer('homework_id')->nullable();
            $table->integer('remark_id')->nullable()->default(0);
            $table->integer('notice_id')->nullable();
            $table->integer('notes_id')->nullable();
            $table->date('notification_date');
            $table->string('token', 1000);
            $table->string('status', 10);
            $table->string('response', 500)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications_log');
    }
};

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
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->integer('ticket_comment_id', true);
            $table->integer('ticket_id');
            $table->char('login_type', 1);
            $table->string('comment', 1000)->nullable();
            $table->timestamp('date')->useCurrentOnUpdate()->useCurrent();
            $table->string('status', 20);
            $table->text('appointment_date_time')->nullable();
            $table->integer('commented_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
    }
};

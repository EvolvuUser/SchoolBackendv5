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
        Schema::create('emails_smtp_details', function (Blueprint $table) {
            $table->integer('email_smtp_id', true);
            $table->string('email', 30);
            $table->string('mail_host', 30);
            $table->integer('mail_port');
            $table->string('mail_username', 30);
            $table->string('mail_password', 30);
            $table->string('mail_encryption', 5);
            $table->string('mail_from_address', 30);
            $table->string('mail_from_name', 30);
            $table->integer('priority');
            $table->string('active', 1)->default('Y');
            $table->integer('daily_limit');
            $table->integer('emails_sent_today');
            $table->date('last_sent_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails_smtp_details');
    }
};

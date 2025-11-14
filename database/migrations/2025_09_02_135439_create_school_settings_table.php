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
        Schema::create('school_settings', function (Blueprint $table) {
            $table->integer('school_settings_id', true);
            $table->integer('school_id')->nullable();
            $table->string('institute_name', 100);
            $table->string('default_pwd', 20);
            $table->string('short_name', 10);
            $table->string('staffuser_suffix', 10);
            $table->string('support_email_id', 25);
            $table->string('school_logo', 30);
            $table->string('website_url', 30);
            $table->string('school_email_id', 30);
            $table->string('uploadfiles_url', 100)->nullable();
            $table->string('redington_api_key', 250);
            $table->char('whatsapp_integration', 1)->default('N');
            $table->char('sms_integration', 1)->default('N');
            $table->char('is_active', 1)->default('N');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};

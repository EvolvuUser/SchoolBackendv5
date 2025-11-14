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
        Schema::create('settings', function (Blueprint $table) {
            $table->integer('setting_id', true)->unique('setting_id');
            $table->string('institute_name', 100);
            $table->string('address', 200);
            $table->string('phone_number', 12);
            $table->string('page_title', 100);
            $table->string('page_meta_tag', 50);
            $table->string('default_pwd', 20);
            $table->string('short_name', 10);
            $table->string('staffuser_suffix', 10);
            $table->string('support_email_id', 25);
            $table->string('school_logo', 30);
            $table->string('website_url', 30)->nullable();
            $table->date('academic_yr_from');
            $table->date('academic_yr_to');
            $table->string('academic_yr', 11);
            $table->char('active', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};

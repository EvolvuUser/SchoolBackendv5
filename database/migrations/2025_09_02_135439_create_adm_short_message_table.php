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
        Schema::create('adm_short_message', function (Blueprint $table) {
            $table->integer('short_msg_id', true);
            $table->string('message', 300);
            $table->date('msg_date');
            $table->string('send_as', 5);
            $table->string('adm_form_pk', 20);
            $table->integer('created_by');
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adm_short_message');
    }
};

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
        Schema::create('meeting_urls', function (Blueprint $table) {
            $table->bigInteger('meeting_urls_id', true);
            $table->bigInteger('meeting_info_id')->index('meeting_urls_meeting_info_id_foreign');
            $table->string('meeting_urls');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_urls');
    }
};

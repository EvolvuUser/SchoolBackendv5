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
        Schema::create('evolvu_updates', function (Blueprint $table) {
            $table->integer('update_id', true);
            $table->string('title', 100);
            $table->string('description', 100);
            $table->date('publish_date');
            $table->date('expiry_date');
            $table->char('publish', 1);
            $table->char('isDelete', 1);
            $table->char('role', 1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evolvu_updates');
    }
};

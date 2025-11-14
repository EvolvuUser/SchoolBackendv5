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
        Schema::create('library_member', function (Blueprint $table) {
            $table->integer('member_id');
            $table->char('member_type', 1);
            $table->date('joining_date');
            $table->char('status', 1);

            $table->primary(['member_id', 'member_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('library_member');
    }
};

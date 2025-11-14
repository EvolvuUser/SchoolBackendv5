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
        Schema::create('subscription_issues', function (Blueprint $table) {
            $table->integer('subscription_issue_id');
            $table->integer('subscription_vol_id');
            $table->integer('issue');
            $table->date('receive_by_date');
            $table->date('date_received');
            $table->string('status', 15);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_issues');
    }
};

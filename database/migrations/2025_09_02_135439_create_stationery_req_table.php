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
        Schema::create('stationery_req', function (Blueprint $table) {
            $table->integer('requisition_id', true);
            $table->integer('stationery_id')->index('stationery_id');
            $table->date('date');
            $table->integer('quantity');
            $table->text('description');
            $table->integer('staff_id')->index('staff_id');
            $table->char('status', 1);
            $table->text('comments');
            $table->integer('approved_by')->nullable()->index('approved_by');
            $table->date('approved_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stationery_req');
    }
};

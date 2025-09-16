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
        Schema::create('leave_application', function (Blueprint $table) {
            $table->integer('leave_app_id', true);
            $table->integer('staff_id')->index('staff_id');
            $table->integer('leave_type_id')->index('leave_type_id');
            $table->date('leave_start_date');
            $table->date('leave_end_date');
            $table->decimal('no_of_days', 4, 1);
            $table->integer('approved_by')->nullable()->index('approved_by');
            $table->char('status', 1);
            $table->string('reason', 500)->nullable();
            $table->string('reason_for_rejection', 500)->nullable();
            $table->string('academic_yr', 11);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_application');
    }
};

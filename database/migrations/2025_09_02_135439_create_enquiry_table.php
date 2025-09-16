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
        Schema::create('enquiry', function (Blueprint $table) {
            $table->integer('enq_id', true);
            $table->string('stu_name', 100);
            $table->string('stu_email', 100);
            $table->timestamp('date')->useCurrent();
            $table->string('stu_mob', 12);
            $table->string('contacted', 5);
            $table->string('call_status', 10);
            $table->date('walkin_date');
            $table->string('comment', 100);
            $table->string('category', 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enquiry');
    }
};

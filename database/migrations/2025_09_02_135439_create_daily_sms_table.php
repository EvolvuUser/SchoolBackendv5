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
        Schema::create('daily_sms', function (Blueprint $table) {
            $table->integer('parent_id');
            $table->integer('student_id');
            $table->string('phone', 10);
            $table->string('alternate_phone_no', 10);
            $table->boolean('homework')->default(false);
            $table->boolean('remark')->default(false);
            $table->boolean('notice')->default(false);
            $table->boolean('note')->default(false);
            $table->boolean('achievement')->default(false);
            $table->dateTime('sms_date');

            $table->unique(['parent_id', 'student_id'], 'parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_sms');
    }
};

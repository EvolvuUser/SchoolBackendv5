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
        Schema::create('daily_sms_for_teacher', function (Blueprint $table) {
            $table->integer('teacher_id');
            $table->integer('class_id')->nullable();
            $table->integer('section_id')->nullable();
            $table->string('phone', 10);
            $table->boolean('homework')->default(false);
            $table->boolean('notice')->default(false);
            $table->boolean('note')->default(false);
            $table->timestamp('sms_date')->useCurrentOnUpdate()->useCurrent();
            $table->boolean('staff_notice')->nullable();
            $table->tinyInteger('remark')->default(0);

            $table->unique(['teacher_id', 'class_id', 'section_id'], 'teacher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_sms_for_teacher');
    }
};

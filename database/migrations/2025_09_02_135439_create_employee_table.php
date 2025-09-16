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
        Schema::create('employee', function (Blueprint $table) {
            $table->text('emp_id');
            $table->text('card_id');
            $table->text('emp_name');
            $table->text('id_card');
            $table->boolean('no_sign');
            $table->text('depart_id');
            $table->text('job_id');
            $table->text('rule_id');
            $table->text('edu_id');
            $table->text('native_id');
            $table->text('nation_id');
            $table->text('status_id');
            $table->text('dorm_id');
            $table->text('polity_id');
            $table->text('position_id');
            $table->text('gd_school');
            $table->dateTime('gd_date');
            $table->text('speciality');
            $table->dateTime('birth_date');
            $table->dateTime('hire_date');
            $table->text('sex');
            $table->text('marriage');
            $table->text('email');
            $table->text('phone_code');
            $table->text('address');
            $table->text('post_code');
            $table->text('ClockMsg');
            $table->binary('photo');
            $table->longText('memo');
            $table->text('card_sn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee');
    }
};

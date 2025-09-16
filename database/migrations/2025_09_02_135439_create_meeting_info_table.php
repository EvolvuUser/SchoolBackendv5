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
        Schema::create('meeting_info', function (Blueprint $table) {
            $table->bigInteger('meeting_info_id', true);
            $table->string('class', 10);
            $table->string('room', 20);
            $table->string('meeting_name');
            $table->string('meeting_id');
            $table->integer('sm_id');
            $table->date('meetingstart_date');
            $table->date('end_date');
            $table->string('attendee_pwd')->nullable();
            $table->string('moderator_pwd')->nullable();
            $table->string('moderator_webcam')->nullable();
            $table->string('random_string')->nullable();
            $table->string('invitation_url')->nullable();
            $table->boolean('archive_status');
            $table->string('start_time', 20);
            $table->string('end_time', 20);
            $table->integer('teacher_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_info');
    }
};

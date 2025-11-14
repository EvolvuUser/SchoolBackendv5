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
        Schema::create('health_activity_record', function (Blueprint $table) {
            $table->integer('ha_id', true);
            $table->string('bmi', 10)->nullable();
            $table->string('partial_curl_up', 10)->nullable();
            $table->string('flex_bent_arm_hang', 10)->nullable();
            $table->string('sit_n_reach', 10)->nullable();
            $table->string('600m_run', 10)->nullable();
            $table->string('flamingo_bel_test', 10)->nullable();
            $table->string('shuttle_run', 10)->nullable();
            $table->string('sprint_dash', 10)->nullable();
            $table->string('standing_vertical_jump', 10)->nullable();
            $table->string('plate_tapping', 10)->nullable();
            $table->string('alternative_handwall_toss', 10)->nullable();
            $table->decimal('vision_re', 3, 1)->nullable();
            $table->decimal('vision_le', 3, 1)->nullable();
            $table->string('ears_left', 10)->nullable();
            $table->string('ears_right', 10)->nullable();
            $table->string('teeth_caries', 10)->nullable();
            $table->string('teeth_tonsils', 10)->nullable();
            $table->string('teeth_gums', 10)->nullable();
            $table->decimal('height', 5)->nullable();
            $table->decimal('weight', 5)->nullable();
            $table->decimal('hip', 5)->nullable();
            $table->decimal('waist', 5)->nullable();
            $table->decimal('pulse', 5)->nullable();
            $table->string('bp', 10)->nullable();
            $table->string('posture_evaluation', 500)->nullable();
            $table->string('strd1', 500)->nullable();
            $table->string('strd2_health_fitness', 500)->nullable();
            $table->string('strd3_sewa', 500)->nullable();
            $table->decimal('m_weight', 4, 1)->nullable();
            $table->decimal('m_height', 4, 1)->nullable();
            $table->decimal('f_weight', 4, 1)->nullable();
            $table->decimal('f_height', 4, 1)->nullable();
            $table->string('family_income', 10)->nullable();
            $table->string('cwsn', 150)->nullable();
            $table->integer('student_id');
            $table->integer('created_by');
            $table->string('academic_yr', 11);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_activity_record');
    }
};

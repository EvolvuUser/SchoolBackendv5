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
        Schema::create('excel', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('stu_name', 100);
            $table->string('stu_email', 70)->unique('stu_email');
            $table->string('stu_mob', 12)->unique('stu_mob');
            $table->string('contacted', 5);
            $table->string('call_status', 15);
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
        Schema::dropIfExists('excel');
    }
};

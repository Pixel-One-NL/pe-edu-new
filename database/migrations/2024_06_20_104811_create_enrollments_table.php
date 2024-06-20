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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            $table->string('eduframe_id')->unique();
            $table->string('planned_course_eduframe_id');
            $table->string('user_eduframe_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status');
            $table->string('graduation_state');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};

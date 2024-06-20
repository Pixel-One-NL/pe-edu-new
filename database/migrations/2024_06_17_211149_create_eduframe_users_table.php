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
        Schema::create('eduframe_users', function (Blueprint $table) {
            $table->id();

            $table->string('eduframe_id')->nullable();
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('slug')->nullable();
            $table->string('avatar_url')->nullable();
            $table->json('roles')->nullable();
            $table->text('notes_user')->nullable();
            $table->text('description')->nullable();
            $table->string('employee_number')->nullable();
            $table->string('student_number')->nullable();
            $table->string('teacher_headline')->nullable();
            $table->text('teacher_description')->nullable();
            $table->integer('teacher_enrollments_count')->nullable();
            $table->string('locale')->nullable();
            $table->boolean('wants_newsletter')->nullable();
            $table->json('address')->nullable();
            $table->json('custom')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eduframe_users');
    }
};

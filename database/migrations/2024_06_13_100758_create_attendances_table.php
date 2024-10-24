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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            $table->string('eduframe_id');
            $table->string('meeting_eduframe_id');
            $table->string('enrollment_eduframe_id');
            $table->string('state');
            $table->text('comment')->nullable();
            $table->boolean('exported')->default(false);
            $table->timestamp('exported_at')->nullable();
            $table->string('eduframe_user_id')->nullable()->after('exported_at');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

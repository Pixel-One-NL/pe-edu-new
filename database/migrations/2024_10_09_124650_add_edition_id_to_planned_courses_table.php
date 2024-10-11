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
        Schema::table('planned_courses', function (Blueprint $table) {
            $table->string('edition_id')->nullable()->after('course_eduframe_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('planned_courses', function (Blueprint $table) {
            $table->dropColumn('edition_id');
        });
    }
};

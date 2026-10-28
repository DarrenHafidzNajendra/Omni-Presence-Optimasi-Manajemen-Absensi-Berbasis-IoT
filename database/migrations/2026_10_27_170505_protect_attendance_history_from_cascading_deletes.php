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
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->dropForeign(['attendance_session_id']);
            $table->dropForeign(['student_id']);
        });

        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->foreign('attendance_session_id')
                ->references('id')
                ->on('attendance_sessions')
                ->restrictOnDelete();
            $table->foreign('student_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->dropForeign(['course_section_id']);
        });

        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->foreign('course_section_id')
                ->references('id')
                ->on('course_sections')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->dropForeign(['course_section_id']);
        });

        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->foreign('course_section_id')
                ->references('id')
                ->on('course_sections')
                ->cascadeOnDelete();
        });

        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->dropForeign(['attendance_session_id']);
            $table->dropForeign(['student_id']);
        });

        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->foreign('attendance_session_id')
                ->references('id')
                ->on('attendance_sessions')
                ->cascadeOnDelete();
            $table->foreign('student_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};

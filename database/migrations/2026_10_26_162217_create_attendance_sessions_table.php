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
        Schema::create('attendance_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_schedule_id')
                ->nullable()
                ->constrained('class_schedules')
                ->nullOnDelete();
            $table->foreignId('course_section_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->enum('session_type', ['teori', 'praktikum']);
            $table->enum('delivery_mode', ['online', 'onsite']);
            $table->string('title');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->dateTime('attendance_opens_at');
            $table->dateTime('attendance_closes_at');
            $table->json('allowed_methods');
            $table->enum('status', ['terjadwal', 'terbuka', 'ditutup'])->default('terjadwal');
            $table->foreignId('opened_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['course_section_id', 'status', 'starts_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};

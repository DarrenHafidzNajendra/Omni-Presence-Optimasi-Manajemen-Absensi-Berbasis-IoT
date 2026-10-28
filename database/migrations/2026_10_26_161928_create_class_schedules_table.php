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
        Schema::create('class_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_section_id')->constrained()->cascadeOnDelete();
            $table->enum('session_type', ['teori', 'praktikum']);
            $table->enum('delivery_mode', ['online', 'onsite']);
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('timezone')->default('Asia/Jakarta');
            $table->string('location')->nullable();
            $table->string('meeting_url')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['course_section_id', 'session_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};

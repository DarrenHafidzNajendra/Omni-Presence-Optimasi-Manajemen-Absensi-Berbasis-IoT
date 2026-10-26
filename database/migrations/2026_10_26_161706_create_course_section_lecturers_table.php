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
        Schema::create('course_section_lecturers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('teaching_type', ['teori', 'praktikum']);
            $table->timestamps();

            $table->unique(
                ['course_section_id', 'user_id', 'teaching_type'],
                'csl_section_user_type_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_section_lecturers');
    }
};

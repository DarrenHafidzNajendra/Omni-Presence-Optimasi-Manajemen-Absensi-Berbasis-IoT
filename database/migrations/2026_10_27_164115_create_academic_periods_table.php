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
        Schema::create('academic_periods', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('academic_year');
            $table->enum('semester', ['ganjil', 'genap']);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->date('ats_starts_on');
            $table->date('ats_ends_on');
            $table->date('uas_starts_on');
            $table->date('uas_ends_on');
            $table->date('replacement_deadline');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_periods');
    }
};

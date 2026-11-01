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
        Schema::table('academic_periods', function (Blueprint $table): void {
            $table->date('ats_replacement_starts_on')->nullable();
            $table->date('ats_replacement_ends_on')->nullable();
            $table->date('uas_replacement_starts_on')->nullable();
            $table->date('uas_replacement_ends_on')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_periods', function (Blueprint $table): void {
            $table->dropColumn([
                'ats_replacement_starts_on',
                'ats_replacement_ends_on',
                'uas_replacement_starts_on',
                'uas_replacement_ends_on',
            ]);
        });
    }
};

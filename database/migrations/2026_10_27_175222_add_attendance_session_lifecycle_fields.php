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
        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->string('session_kind')->nullable();
            $table->foreignId('original_session_id')
                ->nullable()
                ->constrained('attendance_sessions')
                ->restrictOnDelete();
            $table->string('replacement_category')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->unsignedSmallInteger('late_after_minutes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->dropForeign(['original_session_id']);
            $table->dropForeign(['cancelled_by']);
            $table->dropForeign(['closed_by']);
            $table->dropColumn([
                'session_kind',
                'original_session_id',
                'replacement_category',
                'cancelled_at',
                'cancelled_by',
                'cancellation_reason',
                'opened_at',
                'closed_at',
                'closed_by',
                'late_after_minutes',
            ]);
        });
    }
};

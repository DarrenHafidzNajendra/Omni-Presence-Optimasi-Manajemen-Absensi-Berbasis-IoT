<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'attendance_session_id',
        'student_id',
        'method',
        'status',
        'checked_in_at',
        'notes',
    ];

    /**
     * @return BelongsTo<AttendanceSession, $this>
     */
    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Determine if the student is considered present (including late).
     */
    public function isPresent(): bool
    {
        return in_array($this->status, ['hadir', 'terlambat'], true);
    }

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    protected $fillable = [
        'class_schedule_id',
        'course_section_id',
        'session_type',
        'delivery_mode',
        'title',
        'starts_at',
        'ends_at',
        'attendance_opens_at',
        'attendance_closes_at',
        'allowed_methods',
        'status',
    ];

    /**
     * @return BelongsTo<ClassSchedule, $this>
     */
    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    /**
     * @return BelongsTo<CourseSection, $this>
     */
    public function courseSection(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * @return HasMany<AttendanceRecord, $this>
     */
    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'attendance_opens_at' => 'datetime',
            'attendance_closes_at' => 'datetime',
            'allowed_methods' => 'array',
        ];
    }
}

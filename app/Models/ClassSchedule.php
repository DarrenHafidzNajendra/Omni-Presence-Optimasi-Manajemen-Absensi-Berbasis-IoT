<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSchedule extends Model
{
    protected $fillable = [
        'course_section_id',
        'session_type',
        'delivery_mode',
        'day_of_week',
        'starts_at',
        'ends_at',
        'timezone',
        'location',
        'meeting_url',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    /**
     * @return BelongsTo<CourseSection, $this>
     */
    public function courseSection(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class);
    }

    /**
     * @return HasMany<AttendanceSession, $this>
     */
    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class);
    }

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'is_active' => 'boolean',
        ];
    }
}

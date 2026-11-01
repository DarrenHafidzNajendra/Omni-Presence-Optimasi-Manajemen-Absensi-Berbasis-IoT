<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSchedule extends Model
{
    public const DAYS = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

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

    public function dayName(): string
    {
        return self::DAYS[(int) $this->day_of_week] ?? '-';
    }

    public function isScheduledOnDate(DateTimeInterface $date): bool
    {
        if (! $this->is_active || $this->day_of_week === null) {
            return false;
        }

        $targetDate = Carbon::parse($date)->shiftTimezone('Asia/Jakarta');

        if ((int) $targetDate->isoWeekday() !== (int) $this->day_of_week) {
            return false;
        }

        $dateString = $targetDate->format('Y-m-d');

        if ($this->valid_from !== null && $dateString < $this->valid_from->format('Y-m-d')) {
            return false;
        }

        if ($this->valid_until !== null && $dateString > $this->valid_until->format('Y-m-d')) {
            return false;
        }

        return true;
    }

    public function startsAtOnDate(DateTimeInterface $date): Carbon
    {
        $dateString = Carbon::parse($date)->shiftTimezone('Asia/Jakarta')->format('Y-m-d');
        $timeString = substr((string) $this->starts_at, 0, 5);

        return Carbon::parse("{$dateString} {$timeString}:00", 'Asia/Jakarta');
    }

    public function endsAtOnDate(DateTimeInterface $date): Carbon
    {
        $dateString = Carbon::parse($date)->shiftTimezone('Asia/Jakarta')->format('Y-m-d');
        $timeString = substr((string) $this->ends_at, 0, 5);

        return Carbon::parse("{$dateString} {$timeString}:00", 'Asia/Jakarta');
    }

    public function resolveActiveMeetingDate(?DateTimeInterface $now = null): ?Carbon
    {
        $currentTime = $now ? Carbon::parse($now)->shiftTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        if ($this->isScheduledOnDate($currentTime)) {
            return $currentTime->copy()->startOfDay();
        }

        $yesterday = $currentTime->copy()->subDay();
        if ($this->isScheduledOnDate($yesterday)) {
            $yesterdayStart = $this->startsAtOnDate($yesterday);
            $yesterdayMaxClose = $yesterdayStart->copy()->addHours(AttendanceSession::MAX_DURATION_HOURS);

            if ($currentTime->gte($yesterdayStart) && $currentTime->lt($yesterdayMaxClose)) {
                return $yesterday->copy()->startOfDay();
            }
        }

        return null;
    }

    public function canOpenSessionAt(?DateTimeInterface $now = null): bool
    {
        $currentTime = $now ? Carbon::parse($now)->shiftTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');
        $meetingDate = $this->resolveActiveMeetingDate($currentTime);

        if ($meetingDate === null) {
            return false;
        }

        $startsAt = $this->startsAtOnDate($meetingDate);
        $maxClosesAt = $startsAt->copy()->addHours(AttendanceSession::MAX_DURATION_HOURS);

        return $currentTime->gte($startsAt) && $currentTime->lt($maxClosesAt);
    }

    public function findSessionForDate(DateTimeInterface $date): ?AttendanceSession
    {
        $target = Carbon::parse($date)->shiftTimezone('Asia/Jakarta');

        return $this->attendanceSessions()
            ->whereBetween('starts_at', [
                $target->copy()->startOfDay(),
                $target->copy()->endOfDay(),
            ])
            ->orderByDesc('id')
            ->first();
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

<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

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
        'session_kind',
        'original_session_id',
        'replacement_category',
        'cancellation_reason',
        'late_after_minutes',
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
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * @return BelongsTo<AttendanceSession, $this>
     */
    public function originalSession(): BelongsTo
    {
        return $this->belongsTo(self::class, 'original_session_id');
    }

    /**
     * @return HasMany<AttendanceSession, $this>
     */
    public function replacementSessions(): HasMany
    {
        return $this->hasMany(self::class, 'original_session_id');
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
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'late_after_minutes' => 'integer',
        ];
    }

    public function isScheduled(): bool
    {
        return $this->status === 'terjadwal';
    }

    public function isOpen(): bool
    {
        return $this->status === 'terbuka';
    }

    public function isClosed(): bool
    {
        return $this->status === 'ditutup';
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function isReplacement(): bool
    {
        return $this->session_kind === 'replacement';
    }

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function open(User $user, ?DateTimeInterface $now = null): self
    {
        Gate::forUser($user)->authorize('open', $this);

        if ($this->isCancelled()) {
            throw new DomainException('Sesi absensi yang telah dibatalkan tidak dapat dibuka.');
        }

        if ($this->isClosed()) {
            throw new DomainException('Sesi absensi yang sudah ditutup tidak dapat dibuka kembali.');
        }

        if (! $this->isScheduled()) {
            throw new DomainException("Hanya sesi berstatus 'terjadwal' yang dapat dibuka (status saat ini: {$this->status}).");
        }

        $currentTime = $now ? Carbon::parse($now)->shiftTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');
        $opensAt = Carbon::parse($this->attendance_opens_at)->shiftTimezone('Asia/Jakarta');
        $closesAt = Carbon::parse($this->attendance_closes_at)->shiftTimezone('Asia/Jakarta');

        if ($currentTime->lt($opensAt)) {
            throw new DomainException("Sesi absensi belum dapat dibuka sebelum waktu pembukaan ({$opensAt->toDateTimeString()}).");
        }

        if ($currentTime->gt($closesAt)) {
            throw new DomainException("Batas waktu absensi untuk sesi ini telah berakhir ({$closesAt->toDateTimeString()}).");
        }

        if ($this->isReplacement()) {
            $section = $this->courseSection;
            $academicPeriod = $section?->academicPeriod;
            if ($academicPeriod) {
                $category = $this->replacement_category;
                $periodEndsOn = $category === 'ats' ? $academicPeriod->ats_replacement_ends_on : $academicPeriod->uas_replacement_ends_on;

                if ($periodEndsOn) {
                    $periodCutoff = Carbon::parse($periodEndsOn)->shiftTimezone('Asia/Jakarta')->endOfDay();
                    if ($currentTime->gt($periodCutoff)) {
                        throw new DomainException(sprintf(
                            'Batas waktu periode pengganti %s telah berakhir (%s). Sesi tidak dapat dibuka.',
                            strtoupper((string) $category),
                            $periodEndsOn->format('d/m/Y')
                        ));
                    }
                }
            }
        }

        $this->status = 'terbuka';
        $this->opened_at = $currentTime;
        $this->opened_by = $user->getKey();
        $this->save();

        return $this;
    }

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function close(User $user, ?DateTimeInterface $now = null): self
    {
        Gate::forUser($user)->authorize('close', $this);

        if ($this->isCancelled()) {
            throw new DomainException('Sesi absensi yang telah dibatalkan tidak dapat ditutup.');
        }

        if ($this->isClosed()) {
            throw new DomainException('Sesi absensi sudah ditutup.');
        }

        if (! $this->isOpen()) {
            throw new DomainException("Hanya sesi berstatus 'terbuka' yang dapat ditutup (status saat ini: {$this->status}).");
        }

        $currentTime = $now ? Carbon::parse($now)->shiftTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        return DB::transaction(function () use ($user, $currentTime): self {
            $this->status = 'ditutup';
            $this->closed_at = $currentTime;
            $this->closed_by = $user->getKey();
            $this->save();

            $unrecordedStudents = $this->getUnrecordedActiveStudents();

            foreach ($unrecordedStudents as $student) {
                $record = AttendanceRecord::query()->firstOrCreate(
                    [
                        'attendance_session_id' => $this->getKey(),
                        'student_id' => $student->getKey(),
                    ],
                    [
                        'method' => 'manual',
                        'status' => 'tidak_hadir',
                        'checked_in_at' => null,
                        'notes' => 'Otomatis dibuat saat sesi ditutup.',
                    ]
                );

                if ($record->wasRecentlyCreated && $user->getKey()) {
                    $record->recorded_by = $user->getKey();
                    $record->save();
                }
            }

            return $this;
        });
    }

    /**
     * @throws AuthorizationException
     * @throws DomainException
     * @throws InvalidArgumentException
     */
    public function cancel(User $user, string $reason, ?DateTimeInterface $now = null): self
    {
        Gate::forUser($user)->authorize('cancel', $this);

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw new InvalidArgumentException('Alasan pembatalan sesi tidak boleh kosong.');
        }

        if ($this->isCancelled()) {
            throw new DomainException('Sesi absensi sudah pernah dibatalkan.');
        }

        if ($this->isClosed()) {
            throw new DomainException('Sesi absensi yang sudah ditutup tidak dapat dibatalkan melalui alur biasa.');
        }

        if ($this->opened_at !== null) {
            throw new DomainException('Sesi absensi yang sudah dilaksanakan atau dibuka tidak dapat dibatalkan.');
        }

        if ($this->attendanceRecords()->exists()) {
            throw new DomainException('Sesi absensi yang telah memiliki catatan presensi tidak dapat dibatalkan.');
        }

        $currentTime = $now ? Carbon::parse($now)->shiftTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        $this->cancelled_at = $currentTime;
        $this->cancelled_by = $user->getKey();
        $this->cancellation_reason = $trimmedReason;
        $this->save();

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     * @throws InvalidArgumentException
     */
    public function createReplacement(User $user, array $data): self
    {
        $section = $this->courseSection;
        if (! $section) {
            throw new InvalidArgumentException('Sesi asal tidak memiliki kelas perkuliahan yang valid.');
        }

        Gate::forUser($user)->authorize('create', [self::class, $section]);

        if (isset($data['course_section_id']) && (int) $data['course_section_id'] !== (int) $section->getKey()) {
            throw new InvalidArgumentException('CourseSection sesi pengganti harus sama dengan sesi asal.');
        }

        if (isset($data['replacement_category']) && $data['replacement_category'] !== null) {
            $category = strtolower(trim((string) $data['replacement_category']));
            if ($category === '') {
                throw new InvalidArgumentException('Kategori sesi pengganti tidak boleh kosong.');
            }

            if (! in_array($category, ['ats', 'uas'], true)) {
                throw new InvalidArgumentException('Kategori sesi pengganti tidak valid.');
            }

            $academicPeriod = $section->academicPeriod;
            if (! $academicPeriod) {
                throw new InvalidArgumentException('Periode akademik untuk kelas ini belum ditentukan.');
            }

            $periodStartsOn = $category === 'ats' ? $academicPeriod->ats_replacement_starts_on : $academicPeriod->uas_replacement_starts_on;
            $periodEndsOn = $category === 'ats' ? $academicPeriod->ats_replacement_ends_on : $academicPeriod->uas_replacement_ends_on;

            if (! $periodStartsOn || ! $periodEndsOn) {
                throw new InvalidArgumentException(sprintf('Periode pengganti %s belum diatur pada periode akademik.', strtoupper($category)));
            }

            if (isset($data['starts_at'])) {
                $sessionCarbon = Carbon::parse($data['starts_at']);
                $sessionDate = $sessionCarbon->format('Y-m-d');
                $startDate = $periodStartsOn->format('Y-m-d');
                $endDate = $periodEndsOn->format('Y-m-d');

                if ($sessionCarbon->isWeekend()) {
                    throw new InvalidArgumentException('Sesi pengganti hanya boleh dilaksanakan pada hari kerja (Senin–Jumat).');
                }

                if ($sessionDate < $startDate || $sessionDate > $endDate) {
                    throw new InvalidArgumentException(sprintf(
                        'Tanggal sesi pengganti harus berada dalam periode pengganti %s (%s s/d %s).',
                        strtoupper($category),
                        $periodStartsOn->format('d/m/Y'),
                        $periodEndsOn->format('d/m/Y')
                    ));
                }
            }

            $data['replacement_category'] = $category;
        }

        $payload = array_merge($data, [
            'course_section_id' => $section->getKey(),
            'session_kind' => 'replacement',
            'original_session_id' => $this->getKey(),
            'status' => 'terjadwal',
        ]);

        return self::query()->create($payload);
    }

    /**
     * @return BaseCollection<int, User>
     */
    public function getUnrecordedActiveStudents(): BaseCollection
    {
        $section = $this->courseSection;
        if (! $section) {
            return collect();
        }

        $recordedStudentIds = $this->attendanceRecords()->pluck('student_id');

        return $section->enrollments()
            ->where('status', 'aktif')
            ->whereHas('student', function ($query): void {
                $query->where('is_active', true);
            })
            ->whereNotIn('student_id', $recordedStudentIds)
            ->with('student')
            ->get()
            ->pluck('student');
    }

    /**
     * @return EloquentCollection<int, self>
     */
    public static function closeExpiredSessions(?DateTimeInterface $now = null, ?User $systemUser = null): EloquentCollection
    {
        $currentTime = $now ? Carbon::parse($now)->shiftTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        $expiredSessions = self::query()
            ->where('status', 'terbuka')
            ->whereNull('cancelled_at')
            ->where('attendance_closes_at', '<=', $currentTime)
            ->get();

        foreach ($expiredSessions as $session) {
            DB::transaction(function () use ($session, $systemUser, $currentTime): void {
                $session->status = 'ditutup';
                $session->closed_at = $currentTime;
                $session->closed_by = $systemUser?->getKey();
                $session->save();

                $unrecordedStudents = $session->getUnrecordedActiveStudents();
                foreach ($unrecordedStudents as $student) {
                    AttendanceRecord::query()->firstOrCreate(
                        [
                            'attendance_session_id' => $session->getKey(),
                            'student_id' => $student->getKey(),
                        ],
                        [
                            'method' => 'manual',
                            'status' => 'tidak_hadir',
                            'recorded_by' => $systemUser?->getKey(),
                            'checked_in_at' => null,
                            'notes' => 'Otomatis dibuat saat sesi kedaluwarsa ditutup sistem.',
                        ]
                    );
                }
            });
        }

        return $expiredSessions;
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'terjadwal');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'terbuka');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'ditutup');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->whereNotNull('cancelled_at');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at');
    }

    /**
     * Determine if an unexecuted replacement session has expired past its period or schedule.
     */
    public function isUnexecutedOverdue(?DateTimeInterface $now = null): bool
    {
        if (! $this->isReplacement() || ! $this->isScheduled()) {
            return false;
        }

        $currentTime = $now ? Carbon::parse($now)->shiftTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        if ($this->attendance_closes_at && $currentTime->gt(Carbon::parse($this->attendance_closes_at)->shiftTimezone('Asia/Jakarta'))) {
            return true;
        }

        $section = $this->courseSection;
        $academicPeriod = $section?->academicPeriod;
        if ($academicPeriod) {
            $category = $this->replacement_category;
            $periodEndsOn = $category === 'ats' ? $academicPeriod->ats_replacement_ends_on : $academicPeriod->uas_replacement_ends_on;

            if ($periodEndsOn) {
                $periodCutoff = Carbon::parse($periodEndsOn)->shiftTimezone('Asia/Jakarta')->endOfDay();
                if ($currentTime->gt($periodCutoff)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeExpired(Builder $query, ?DateTimeInterface $at = null): Builder
    {
        $currentTime = $at ? Carbon::instance($at)->setTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        return $query->where('status', 'terbuka')
            ->whereNull('cancelled_at')
            ->where('attendance_closes_at', '<=', $currentTime);
    }
}

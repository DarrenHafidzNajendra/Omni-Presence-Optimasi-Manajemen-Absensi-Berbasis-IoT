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
    public const MAX_DURATION_HOURS = 24;

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

    public function isExpiredAt(?DateTimeInterface $now = null): bool
    {
        if ($this->isCancelled() || $this->isClosed()) {
            return false;
        }

        $currentTime = $now ? Carbon::parse($now)->shiftTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        if ($this->starts_at !== null) {
            $maxWindowCutoff = Carbon::parse($this->starts_at)->shiftTimezone('Asia/Jakarta')->addHours(self::MAX_DURATION_HOURS);
            if ($currentTime->gte($maxWindowCutoff)) {
                return true;
            }
        }

        if ($this->attendance_closes_at !== null) {
            $closesAt = Carbon::parse($this->attendance_closes_at)->shiftTimezone('Asia/Jakarta');
            if ($currentTime->gte($closesAt)) {
                return true;
            }
        }

        if ($this->isReplacement() && $this->isScheduled()) {
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
        }

        return false;
    }

    public function canBeOpenedAt(?DateTimeInterface $now = null): bool
    {
        if (! $this->isScheduled() || $this->isCancelled()) {
            return false;
        }

        $currentTime = $now ? Carbon::parse($now)->shiftTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        if ($this->isExpiredAt($currentTime)) {
            return false;
        }

        if ($this->class_schedule_id !== null && $this->starts_at !== null) {
            $startsAt = Carbon::parse($this->starts_at)->shiftTimezone('Asia/Jakarta');
            if ($currentTime->lt($startsAt)) {
                return false;
            }
        }

        if ($this->attendance_opens_at !== null) {
            $opensAt = Carbon::parse($this->attendance_opens_at)->shiftTimezone('Asia/Jakarta');
            if ($currentTime->lt($opensAt)) {
                return false;
            }
        }

        return true;
    }

    public function displayStatus(?DateTimeInterface $now = null): string
    {
        if ($this->isCancelled()) {
            return 'dibatalkan';
        }

        if ($this->isClosed()) {
            return 'ditutup';
        }

        if ($this->isExpiredAt($now)) {
            return 'kedaluwarsa';
        }

        return (string) $this->status;
    }

    /**
     * @param  array<string, mixed>  $options
     *
     * @throws AuthorizationException
     * @throws DomainException
     * @throws InvalidArgumentException
     */
    public static function openFromSchedule(ClassSchedule $schedule, User $user, array $options = [], ?DateTimeInterface $now = null): self
    {
        $section = $schedule->courseSection;
        if (! $section) {
            throw new InvalidArgumentException('Jadwal perkuliahan tidak memiliki kelas yang valid.');
        }

        Gate::forUser($user)->authorize('create', [self::class, $section]);

        if (! $schedule->is_active) {
            throw new DomainException('Jadwal perkuliahan sedang tidak aktif.');
        }

        $currentTime = $now ? Carbon::parse($now)->shiftTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');
        $meetingDate = $schedule->resolveActiveMeetingDate($currentTime);

        if ($meetingDate === null) {
            throw new DomainException('Sesi absensi belum dapat dibuka sebelum jadwal dimulai atau di luar hari jadwal perkuliahan.');
        }

        $startsAt = $schedule->startsAtOnDate($meetingDate);
        $endsAt = $schedule->endsAtOnDate($meetingDate);
        $maxClosesAt = $startsAt->copy()->addHours(self::MAX_DURATION_HOURS);

        if ($currentTime->lt($startsAt)) {
            throw new DomainException("Sesi absensi belum dapat dibuka sebelum waktu mulai jadwal ({$startsAt->toDateTimeString()}).");
        }

        if ($currentTime->gte($maxClosesAt)) {
            throw new DomainException("Batas waktu 24 jam absensi untuk jadwal ini telah berakhir ({$maxClosesAt->toDateTimeString()}).");
        }

        return DB::transaction(function () use ($schedule, $section, $user, $options, $currentTime, $startsAt, $endsAt, $maxClosesAt): self {
            ClassSchedule::query()->whereKey($schedule->getKey())->lockForUpdate()->first();

            $existingSessions = self::query()
                ->where('course_section_id', $section->getKey())
                ->where(function (Builder $query) use ($schedule, $startsAt): void {
                    $query->where(function (Builder $q) use ($schedule, $startsAt): void {
                        $q->where('class_schedule_id', $schedule->getKey())
                            ->whereBetween('starts_at', [
                                $startsAt->copy()->startOfDay(),
                                $startsAt->copy()->endOfDay(),
                            ]);
                    })->orWhere(function (Builder $q) use ($startsAt): void {
                        $q->where('session_kind', 'regular')
                            ->where('starts_at', $startsAt);
                    });
                })
                ->lockForUpdate()
                ->orderByDesc('id')
                ->get();

            $nonCancelled = $existingSessions->first(fn (self $session): bool => ! $session->isCancelled());

            if ($nonCancelled !== null) {
                if ($nonCancelled->isClosed()) {
                    throw new DomainException('Sesi absensi yang sudah ditutup tidak dapat dibuka kembali.');
                }

                if ($nonCancelled->isOpen()) {
                    throw new DomainException('Sesi absensi untuk jadwal dan tanggal pertemuan ini sudah dibuka.');
                }

                if ($nonCancelled->isScheduled()) {
                    if ($nonCancelled->class_schedule_id === null) {
                        $nonCancelled->class_schedule_id = $schedule->getKey();
                    }

                    if ($nonCancelled->attendance_closes_at->lt($currentTime) && $currentTime->lt($maxClosesAt)) {
                        $nonCancelled->attendance_closes_at = $maxClosesAt;
                    }

                    $nonCancelled->save();

                    return $nonCancelled->open($user, $currentTime);
                }
            }

            if ($existingSessions->isNotEmpty()) {
                throw new DomainException('Sesi absensi yang telah dibatalkan tidak dapat dibuka.');
            }

            $meetingNumber = self::query()
                ->where('course_section_id', $section->getKey())
                ->whereNull('cancelled_at')
                ->count() + 1;

            $title = isset($options['title']) && trim((string) $options['title']) !== ''
                ? trim((string) $options['title'])
                : "Pertemuan {$meetingNumber}";

            $allowedMethods = isset($options['allowed_methods']) && is_array($options['allowed_methods']) && count($options['allowed_methods']) > 0
                ? array_values($options['allowed_methods'])
                : ['manual', 'qr_code', 'face_recognition'];

            $lateAfterMinutes = array_key_exists('late_after_minutes', $options)
                ? $options['late_after_minutes']
                : 15;

            $session = new self([
                'class_schedule_id' => $schedule->getKey(),
                'course_section_id' => $section->getKey(),
                'session_type' => $schedule->session_type,
                'delivery_mode' => $schedule->delivery_mode,
                'title' => $title,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'attendance_opens_at' => $startsAt,
                'attendance_closes_at' => $maxClosesAt,
                'allowed_methods' => $allowedMethods,
                'status' => 'terbuka',
                'session_kind' => 'regular',
                'late_after_minutes' => $lateAfterMinutes,
            ]);
            $session->opened_at = $currentTime;
            $session->opened_by = $user->getKey();
            $session->save();

            return $session;
        });
    }

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function open(User $user, ?DateTimeInterface $now = null): self
    {
        Gate::forUser($user)->authorize('open', $this);

        $currentTime = $now ? Carbon::parse($now)->shiftTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        return DB::transaction(function () use ($user, $currentTime): self {
            $lockedSession = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedSession->isCancelled()) {
                throw new DomainException('Sesi absensi yang telah dibatalkan tidak dapat dibuka.');
            }

            if ($lockedSession->isClosed()) {
                throw new DomainException('Sesi absensi yang sudah ditutup tidak dapat dibuka kembali.');
            }

            if (! $lockedSession->isScheduled()) {
                throw new DomainException("Hanya sesi berstatus 'terjadwal' yang dapat dibuka (status saat ini: {$lockedSession->status}).");
            }

            $opensAt = Carbon::parse($lockedSession->attendance_opens_at)->shiftTimezone('Asia/Jakarta');
            $closesAt = Carbon::parse($lockedSession->attendance_closes_at)->shiftTimezone('Asia/Jakarta');

            if ($lockedSession->class_schedule_id !== null && $lockedSession->starts_at !== null) {
                $startsAt = Carbon::parse($lockedSession->starts_at)->shiftTimezone('Asia/Jakarta');
                if ($currentTime->lt($startsAt)) {
                    throw new DomainException("Sesi absensi belum dapat dibuka sebelum waktu mulai jadwal ({$startsAt->toDateTimeString()}).");
                }
            }

            if ($currentTime->lt($opensAt)) {
                throw new DomainException("Sesi absensi belum dapat dibuka sebelum waktu pembukaan ({$opensAt->toDateTimeString()}).");
            }

            if ($currentTime->gt($closesAt)) {
                throw new DomainException("Batas waktu absensi untuk sesi ini telah berakhir ({$closesAt->toDateTimeString()}).");
            }

            if ($lockedSession->isReplacement()) {
                $section = $lockedSession->courseSection;
                $academicPeriod = $section?->academicPeriod;
                if ($academicPeriod) {
                    $category = $lockedSession->replacement_category;
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

            if ($lockedSession->starts_at !== null) {
                $maxWindowCutoff = Carbon::parse($lockedSession->starts_at)->shiftTimezone('Asia/Jakarta')->addHours(self::MAX_DURATION_HOURS);
                if ($currentTime->gte($maxWindowCutoff)) {
                    throw new DomainException("Batas waktu absensi untuk sesi ini telah berakhir ({$maxWindowCutoff->toDateTimeString()}).");
                }
            }

            $lockedSession->status = 'terbuka';
            $lockedSession->opened_at = $currentTime;
            $lockedSession->opened_by = $user->getKey();
            $lockedSession->save();

            $this->setRawAttributes($lockedSession->getAttributes(), true);

            return $this;
        });
    }

    /**
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function close(User $user, ?DateTimeInterface $now = null): self
    {
        Gate::forUser($user)->authorize('close', $this);

        $currentTime = $now ? Carbon::parse($now)->shiftTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        return DB::transaction(function () use ($user, $currentTime): self {
            $lockedSession = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedSession->isCancelled()) {
                throw new DomainException('Sesi absensi yang telah dibatalkan tidak dapat ditutup.');
            }

            if ($lockedSession->isClosed()) {
                throw new DomainException('Sesi absensi sudah ditutup.');
            }

            if (! $lockedSession->isOpen()) {
                throw new DomainException("Hanya sesi berstatus 'terbuka' yang dapat ditutup (status saat ini: {$lockedSession->status}).");
            }

            $lockedSession->status = 'ditutup';
            $lockedSession->closed_at = $currentTime;
            $lockedSession->closed_by = $user->getKey();
            $lockedSession->save();

            $unrecordedStudents = $lockedSession->getUnrecordedActiveStudents();

            foreach ($unrecordedStudents as $student) {
                $record = AttendanceRecord::query()->firstOrCreate(
                    [
                        'attendance_session_id' => $lockedSession->getKey(),
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

            $this->setRawAttributes($lockedSession->getAttributes(), true);

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

        $currentTime = $now ? Carbon::parse($now)->shiftTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');

        return DB::transaction(function () use ($user, $trimmedReason, $currentTime): self {
            $lockedSession = self::query()->whereKey($this->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedSession->isCancelled()) {
                throw new DomainException('Sesi absensi sudah pernah dibatalkan.');
            }

            if ($lockedSession->isClosed()) {
                throw new DomainException('Sesi absensi yang sudah ditutup tidak dapat dibatalkan melalui alur biasa.');
            }

            if ($lockedSession->opened_at !== null) {
                throw new DomainException('Sesi absensi yang sudah dilaksanakan atau dibuka tidak dapat dibatalkan.');
            }

            if ($lockedSession->attendanceRecords()->exists()) {
                throw new DomainException('Sesi absensi yang telah memiliki catatan presensi tidak dapat dibatalkan.');
            }

            $lockedSession->cancelled_at = $currentTime;
            $lockedSession->cancelled_by = $user->getKey();
            $lockedSession->cancellation_reason = $trimmedReason;
            $lockedSession->save();

            $this->setRawAttributes($lockedSession->getAttributes(), true);

            return $this;
        });
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
            ->expired($currentTime)
            ->get();

        $closedSessions = new EloquentCollection;

        foreach ($expiredSessions as $session) {
            $wasClosed = DB::transaction(function () use ($session, $systemUser, $currentTime): bool {
                $lockedSession = self::query()->whereKey($session->getKey())->lockForUpdate()->first();

                if (! $lockedSession || $lockedSession->isCancelled() || $lockedSession->isClosed()) {
                    return false;
                }

                $wasScheduled = $lockedSession->isScheduled();

                $lockedSession->status = 'ditutup';
                $lockedSession->closed_at = $currentTime;
                $lockedSession->closed_by = $systemUser?->getKey();
                $lockedSession->save();

                $unrecordedStudents = $lockedSession->getUnrecordedActiveStudents();
                foreach ($unrecordedStudents as $student) {
                    AttendanceRecord::query()->firstOrCreate(
                        [
                            'attendance_session_id' => $lockedSession->getKey(),
                            'student_id' => $student->getKey(),
                        ],
                        [
                            'method' => 'manual',
                            'status' => 'tidak_hadir',
                            'recorded_by' => $systemUser?->getKey(),
                            'checked_in_at' => null,
                            'notes' => $wasScheduled
                                ? 'Otomatis dibuat saat sesi tidak dibuka hingga melewati batas 24 jam.'
                                : 'Otomatis dibuat saat sesi kedaluwarsa ditutup sistem.',
                        ]
                    );
                }

                return true;
            });

            if ($wasClosed) {
                $closedSessions->push($session->fresh());
            }
        }

        return $closedSessions;
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
        $currentTime = $at ? Carbon::parse($at)->shiftTimezone('Asia/Jakarta') : Carbon::now('Asia/Jakarta');
        $maxStartCutoff = $currentTime->copy()->subHours(self::MAX_DURATION_HOURS);

        return $query->whereNull('cancelled_at')
            ->where(function (Builder $q) use ($currentTime, $maxStartCutoff): void {
                $q->where(function (Builder $openQuery) use ($currentTime, $maxStartCutoff): void {
                    $openQuery->where('status', 'terbuka')
                        ->where(function (Builder $timeQuery) use ($currentTime, $maxStartCutoff): void {
                            $timeQuery->where('attendance_closes_at', '<=', $currentTime)
                                ->orWhere('starts_at', '<=', $maxStartCutoff);
                        });
                })->orWhere(function (Builder $scheduledQuery) use ($maxStartCutoff): void {
                    $scheduledQuery->where('status', 'terjadwal')
                        ->where('starts_at', '<=', $maxStartCutoff);
                });
            });
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\ClassSchedule;
use App\Models\CourseSection;
use App\Services\AttendanceRecapService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class AttendanceSessionController extends Controller
{
    /**
     * Display cumulative attendance recap for a course section.
     */
    public function recap(CourseSection $courseSection, AttendanceRecapService $recapService): View
    {
        Gate::authorize('viewAny', [AttendanceSession::class, $courseSection]);

        $recap = $recapService->getRecap($courseSection);

        return view('attendance-sessions.recap', [
            'courseSection' => $courseSection,
            'recap' => $recap,
        ]);
    }

    /**
     * Display a listing of attendance sessions for a course section.
     */
    public function index(CourseSection $courseSection): View
    {
        Gate::authorize('viewAny', [AttendanceSession::class, $courseSection]);

        $sessions = $courseSection->attendanceSessions()
            ->with(['openedBy', 'closedBy', 'cancelledBy'])
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->paginate(15);

        return view('attendance-sessions.index', compact('courseSection', 'sessions'));
    }

    /**
     * Show the form for creating a new regular attendance session.
     */
    public function create(CourseSection $courseSection): View
    {
        Gate::authorize('create', [AttendanceSession::class, $courseSection]);

        $schedules = $courseSection->schedules()->get();

        return view('attendance-sessions.create', [
            'courseSection' => $courseSection,
            'schedules' => $schedules,
        ]);
    }

    /**
     * Show the form for creating a replacement attendance session.
     */
    public function createReplacement(CourseSection $courseSection, AttendanceSession $attendanceSession): View
    {
        Gate::authorize('create', [AttendanceSession::class, $courseSection]);

        if ((int) $attendanceSession->course_section_id !== (int) $courseSection->id) {
            abort(404, 'Sesi asal tidak ditemukan pada kelas ini.');
        }

        $courseSection->load('academicPeriod');
        $academicPeriod = $courseSection->academicPeriod;

        return view('attendance-sessions.create-replacement', [
            'courseSection' => $courseSection,
            'originalSession' => $attendanceSession,
            'academicPeriod' => $academicPeriod,
        ]);
    }

    /**
     * Display the specified attendance session.
     */
    public function show(CourseSection $courseSection, AttendanceSession $attendanceSession): View
    {
        Gate::authorize('view', [$attendanceSession, $courseSection]);

        $attendanceSession->load(['openedBy', 'closedBy', 'cancelledBy', 'originalSession', 'replacementSessions', 'classSchedule']);

        $enrollments = $courseSection->enrollments()
            ->where('status', 'aktif')
            ->whereHas('student', function ($query): void {
                $query->where('is_active', true);
            })
            ->with('student')
            ->get();

        $records = $attendanceSession->attendanceRecords()
            ->with(['student', 'recordedBy'])
            ->get()
            ->keyBy('student_id');

        $sessionDate = Carbon::parse($attendanceSession->starts_at)->shiftTimezone('Asia/Jakarta');
        $endOfSessionDate = $sessionDate->copy()->endOfDay();
        $isPastSessionDate = Carbon::now('Asia/Jakarta')->gt($endOfSessionDate);

        $totalActiveStudents = $enrollments->count();
        $counts = [
            'hadir' => 0,
            'terlambat' => 0,
            'izin' => 0,
            'sakit' => 0,
            'tidak_hadir' => 0,
            'belum_dicatat' => 0,
        ];

        foreach ($enrollments as $enrollment) {
            $studentId = $enrollment->student_id;
            $record = $records->get($studentId);

            if (! $record || ! array_key_exists($record->status, $counts)) {
                $counts['belum_dicatat']++;
            } else {
                $counts[$record->status]++;
            }
        }

        $recordedCount = $counts['hadir'] + $counts['terlambat'] + $counts['izin'] + $counts['sakit'] + $counts['tidak_hadir'];
        $presentCount = $counts['hadir'] + $counts['terlambat'];
        $attendancePercentage = $recordedCount > 0
            ? round(($presentCount / $recordedCount) * 100, 1)
            : null;

        $summary = array_merge($counts, [
            'total' => $totalActiveStudents,
            'recorded' => $recordedCount,
            'present' => $presentCount,
            'percentage' => $attendancePercentage,
        ]);

        return view('attendance-sessions.show', [
            'courseSection' => $courseSection,
            'session' => $attendanceSession,
            'enrollments' => $enrollments,
            'records' => $records,
            'isPastSessionDate' => $isPastSessionDate,
            'summary' => $summary,
        ]);
    }

    /**
     * Store a regular attendance session.
     */
    public function store(Request $request, CourseSection $courseSection): RedirectResponse|JsonResponse
    {
        Gate::authorize('create', [AttendanceSession::class, $courseSection]);

        $validator = Validator::make($request->all(), [
            'class_schedule_id' => [
                'nullable',
                'integer',
                Rule::exists(ClassSchedule::class, 'id')->where(function ($query) use ($courseSection): void {
                    $query->where('course_section_id', $courseSection->id);
                }),
            ],
            'session_type' => ['required', Rule::in(['teori', 'praktikum'])],
            'delivery_mode' => ['required', Rule::in(['online', 'onsite'])],
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date_format:Y-m-d H:i'],
            'ends_at' => ['required', 'date_format:Y-m-d H:i'],
            'attendance_opens_at' => ['required', 'date_format:Y-m-d H:i'],
            'attendance_closes_at' => ['required', 'date_format:Y-m-d H:i'],
            'allowed_methods' => ['required', 'array', 'min:1'],
            'allowed_methods.*' => [Rule::in(['qr_code', 'face_recognition', 'manual'])],
            'late_after_minutes' => ['nullable', 'integer', 'min:0', 'max:360'],
        ]);

        $validator->after(function (ValidatorContract $validator) use ($request): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $startsAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $request->input('starts_at'));
            $endsAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $request->input('ends_at'));
            $opensAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $request->input('attendance_opens_at'));
            $closesAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $request->input('attendance_closes_at'));

            if ($endsAt->isBefore($startsAt)) {
                $validator->errors()->add('ends_at', 'Waktu selesai perkuliahan harus setelah atau sama dengan waktu mulai.');
            }

            if ($closesAt->isBefore($opensAt)) {
                $validator->errors()->add('attendance_closes_at', 'Waktu tutup absensi harus setelah atau sama dengan waktu buka absensi.');
            }

            $scheduleId = $request->input('class_schedule_id');
            if ($scheduleId) {
                $schedule = ClassSchedule::find($scheduleId);
                if ($schedule && $schedule->day_of_week) {
                    $dayOfWeekIso = (int) $startsAt->isoWeekday();
                    if ($dayOfWeekIso !== (int) $schedule->day_of_week) {
                        $validator->errors()->add('starts_at', 'Hari pelaksanaan sesi tidak sesuai dengan hari jadwal perkuliahan yang dipilih.');
                    }
                }
            }
        });

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $session = $courseSection->attendanceSessions()->create(array_merge($validated, [
            'status' => 'terjadwal',
            'session_kind' => 'regular',
        ]));

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Sesi absensi berhasil dibuat.',
                'data' => $session,
            ], 201);
        }

        return redirect()
            ->route('course-sections.attendance-sessions.show', [$courseSection, $session])
            ->with('success', 'Sesi absensi berhasil dibuat.');
    }

    /**
     * Store a replacement attendance session.
     */
    public function storeReplacement(Request $request, CourseSection $courseSection, AttendanceSession $attendanceSession): RedirectResponse|JsonResponse
    {
        Gate::authorize('create', [AttendanceSession::class, $courseSection]);

        if ((int) $attendanceSession->course_section_id !== (int) $courseSection->id) {
            abort(404, 'Sesi asal tidak ditemukan pada kelas ini.');
        }

        $validator = Validator::make($request->all(), [
            'replacement_category' => ['required', Rule::in(['ats', 'uas'])],
            'session_type' => ['required', Rule::in(['teori', 'praktikum'])],
            'delivery_mode' => ['required', Rule::in(['online', 'onsite'])],
            'title' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date_format:Y-m-d H:i'],
            'ends_at' => ['required', 'date_format:Y-m-d H:i'],
            'attendance_opens_at' => ['required', 'date_format:Y-m-d H:i'],
            'attendance_closes_at' => ['required', 'date_format:Y-m-d H:i'],
            'allowed_methods' => ['required', 'array', 'min:1'],
            'allowed_methods.*' => [Rule::in(['qr_code', 'face_recognition', 'manual'])],
            'late_after_minutes' => ['nullable', 'integer', 'min:0', 'max:360'],
        ]);

        $validator->after(function (ValidatorContract $validator) use ($request): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $startsAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $request->input('starts_at'));
            $endsAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $request->input('ends_at'));
            $opensAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $request->input('attendance_opens_at'));
            $closesAt = CarbonImmutable::createFromFormat('Y-m-d H:i', $request->input('attendance_closes_at'));

            if ($endsAt->isBefore($startsAt)) {
                $validator->errors()->add('ends_at', 'Waktu selesai perkuliahan harus setelah atau sama dengan waktu mulai.');
            }

            if ($closesAt->isBefore($opensAt)) {
                $validator->errors()->add('attendance_closes_at', 'Waktu tutup absensi harus setelah atau sama dengan waktu buka absensi.');
            }
        });

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        try {
            $replacement = $attendanceSession->createReplacement($request->user(), $data);
        } catch (InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['replacement_category' => $e->getMessage()])->withInput();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Sesi pengganti berhasil dibuat.',
                'data' => $replacement,
            ], 201);
        }

        return redirect()
            ->route('course-sections.attendance-sessions.show', [$courseSection, $replacement])
            ->with('success', 'Sesi pengganti berhasil dibuat.');
    }

    /**
     * Open an attendance session.
     */
    public function open(Request $request, CourseSection $courseSection, AttendanceSession $attendanceSession): RedirectResponse|JsonResponse
    {
        Gate::authorize('open', [$attendanceSession, $courseSection]);

        if ((int) $attendanceSession->course_section_id !== (int) $courseSection->id) {
            abort(404, 'Sesi absensi tidak ditemukan pada kelas ini.');
        }

        try {
            $attendanceSession->open($request->user());
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['session' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Sesi absensi berhasil dibuka.',
                'data' => $attendanceSession->fresh(),
            ]);
        }

        return back()->with('success', 'Sesi absensi berhasil dibuka.');
    }

    /**
     * Close an attendance session.
     */
    public function close(Request $request, CourseSection $courseSection, AttendanceSession $attendanceSession): RedirectResponse|JsonResponse
    {
        Gate::authorize('close', [$attendanceSession, $courseSection]);

        if ((int) $attendanceSession->course_section_id !== (int) $courseSection->id) {
            abort(404, 'Sesi absensi tidak ditemukan pada kelas ini.');
        }

        try {
            $attendanceSession->close($request->user());
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['session' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Sesi absensi berhasil ditutup.',
                'data' => $attendanceSession->fresh(),
            ]);
        }

        return back()->with('success', 'Sesi absensi berhasil ditutup.');
    }

    /**
     * Cancel an attendance session.
     */
    public function cancel(Request $request, CourseSection $courseSection, AttendanceSession $attendanceSession): RedirectResponse|JsonResponse
    {
        Gate::authorize('cancel', [$attendanceSession, $courseSection]);

        if ((int) $attendanceSession->course_section_id !== (int) $courseSection->id) {
            abort(404, 'Sesi absensi tidak ditemukan pada kelas ini.');
        }

        $validator = Validator::make($request->all(), [
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        try {
            $attendanceSession->cancel($request->user(), (string) $request->input('cancellation_reason'));
        } catch (DomainException|InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['cancellation_reason' => $e->getMessage()]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Sesi absensi berhasil dibatalkan.',
                'data' => $attendanceSession->fresh(),
            ]);
        }

        return back()->with('success', 'Sesi absensi berhasil dibatalkan.');
    }

    /**
     * Record or update attendance for a student in this session.
     */
    public function recordAttendance(Request $request, CourseSection $courseSection, AttendanceSession $attendanceSession): RedirectResponse|JsonResponse
    {
        Gate::authorize('manageAttendance', [$attendanceSession, $courseSection]);

        if ((int) $attendanceSession->course_section_id !== (int) $courseSection->id) {
            abort(404, 'Sesi absensi tidak ditemukan pada kelas ini.');
        }

        $sessionDate = Carbon::parse($attendanceSession->starts_at)->shiftTimezone('Asia/Jakarta');
        $endOfSessionDate = $sessionDate->copy()->endOfDay();
        $isPastSessionDate = Carbon::now('Asia/Jakarta')->gt($endOfSessionDate);

        // Jika sudah melewati akhir tanggal sesi lokal Jakarta, hanya Admin yang boleh koreksi
        $user = $request->user();
        if ($isPastSessionDate && $user->role !== 'admin') {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Batas waktu pencatatan oleh dosen telah berakhir. Koreksi hanya dapat dilakukan oleh Admin.'], 403);
            }

            return back()->withErrors(['attendance' => 'Batas waktu pencatatan oleh dosen telah berakhir. Koreksi hanya dapat dilakukan oleh Admin.']);
        }

        $rules = [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('enrollments', 'student_id')->where(function ($query) use ($courseSection): void {
                    $query->where('course_section_id', $courseSection->id)
                        ->where('status', 'aktif');
                }),
            ],
            'status' => ['required', Rule::in(['hadir', 'terlambat', 'izin', 'sakit'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        // Jika koreksi oleh Admin setelah batas waktu, alasan koreksi wajib diisi
        if ($isPastSessionDate && $user->role === 'admin') {
            $rules['notes'] = ['required', 'string', 'min:3', 'max:1000'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();
        $studentId = (int) $validated['student_id'];
        $newStatus = $validated['status'];
        $newNotes = isset($validated['notes']) ? trim((string) $validated['notes']) : null;

        $existingRecord = $attendanceSession->attendanceRecords()->where('student_id', $studentId)->first();

        $currentTime = Carbon::now('Asia/Jakarta');

        if ($existingRecord) {
            // Update record yang sudah ada
            $notesToSave = $existingRecord->notes;
            if ($newNotes !== null && $newNotes !== '') {
                if ($isPastSessionDate && $user->role === 'admin') {
                    $correctionEntry = sprintf('[Koreksi Admin oleh %s pada %s]: %s', $user->name, $currentTime->format('d/m/Y H:i'), $newNotes);
                    $notesToSave = $existingRecord->notes ? $existingRecord->notes."\n".$correctionEntry : $correctionEntry;
                } else {
                    $notesToSave = $newNotes;
                }
            }

            $existingRecord->update([
                'status' => $newStatus,
                'method' => 'manual',
                'checked_in_at' => in_array($newStatus, ['hadir', 'terlambat'], true) ? ($existingRecord->checked_in_at ?? $currentTime) : null,
                'notes' => $notesToSave,
            ]);
            $existingRecord->recorded_by = $user->getKey();
            $existingRecord->save();

            $record = $existingRecord;
        } else {
            // Buat record baru
            $record = $attendanceSession->attendanceRecords()->create([
                'student_id' => $studentId,
                'status' => $newStatus,
                'method' => 'manual',
                'checked_in_at' => in_array($newStatus, ['hadir', 'terlambat'], true) ? $currentTime : null,
                'notes' => $newNotes,
            ]);
            $record->recorded_by = $user->getKey();
            $record->save();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Presensi mahasiswa berhasil disimpan.',
                'data' => $record->fresh(),
            ]);
        }

        return back()->with('success', 'Presensi mahasiswa berhasil disimpan.');
    }
}

<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CourseSectionLecturer;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\AttendanceRecapService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecapTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $assignedLecturer;

    private User $unassignedLecturer;

    private User $studentUser;

    private CourseSection $courseSection;

    private AcademicPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->assignedLecturer = User::factory()->create(['role' => 'dosen']);
        $this->unassignedLecturer = User::factory()->create(['role' => 'dosen']);
        $this->studentUser = User::factory()->create(['role' => 'mahasiswa']);

        $this->period = AcademicPeriod::query()->create([
            'name' => 'Semester Ganjil 2026/2027',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'starts_on' => '2026-08-01',
            'ends_on' => '2027-01-31',
            'ats_starts_on' => '2026-10-05',
            'ats_ends_on' => '2026-10-16',
            'uas_starts_on' => '2027-01-11',
            'uas_ends_on' => '2027-01-22',
            'replacement_deadline' => '2027-01-29',
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'code' => 'CS101',
            'name' => 'Pemrograman Dasar',
        ]);

        $this->courseSection = CourseSection::query()->create([
            'course_id' => $course->id,
            'academic_period_id' => $this->period->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        CourseSectionLecturer::query()->create([
            'course_section_id' => $this->courseSection->id,
            'user_id' => $this->assignedLecturer->id,
            'teaching_type' => 'teori',
        ]);
    }

    public function test_admin_can_view_recap_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('course-sections.attendance-recap', $this->courseSection));

        $response->assertOk();
        $response->assertViewIs('attendance-sessions.recap');
        $response->assertSee('REKAP KEHADIRAN KELAS');
    }

    public function test_assigned_lecturer_can_view_recap_page(): void
    {
        $response = $this->actingAs($this->assignedLecturer)->get(route('course-sections.attendance-recap', $this->courseSection));

        $response->assertOk();
        $response->assertViewIs('attendance-sessions.recap');
    }

    public function test_unassigned_lecturer_cannot_view_recap_page(): void
    {
        $response = $this->actingAs($this->unassignedLecturer)->get(route('course-sections.attendance-recap', $this->courseSection));

        $response->assertForbidden();
    }

    public function test_student_cannot_view_recap_page(): void
    {
        $response = $this->actingAs($this->studentUser)->get(route('course-sections.attendance-recap', $this->courseSection));

        $response->assertForbidden();
    }

    public function test_recap_calculates_correct_totals_and_percentages_for_various_attendance_statuses(): void
    {
        // 2 active students
        $student1 = User::factory()->create(['name' => 'Alice', 'role' => 'mahasiswa', 'is_active' => true]);
        $student2 = User::factory()->create(['name' => 'Bob', 'role' => 'mahasiswa', 'is_active' => true]);

        Enrollment::query()->create([
            'course_section_id' => $this->courseSection->id,
            'student_id' => $student1->id,
            'status' => 'aktif',
        ]);
        Enrollment::query()->create([
            'course_section_id' => $this->courseSection->id,
            'student_id' => $student2->id,
            'status' => 'aktif',
        ]);

        // Create 2 closed sessions (qualifying)
        $session1 = AttendanceSession::query()->create([
            'course_section_id' => $this->courseSection->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan 1',
            'starts_at' => Carbon::now()->subDays(7),
            'ends_at' => Carbon::now()->subDays(7)->addHours(2),
            'attendance_opens_at' => Carbon::now()->subDays(7),
            'attendance_closes_at' => Carbon::now()->subDays(7)->addHours(2),
            'allowed_methods' => ['qr_code'],
            'status' => 'ditutup',
        ]);

        $session2 = AttendanceSession::query()->create([
            'course_section_id' => $this->courseSection->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan 2',
            'starts_at' => Carbon::now()->subDays(2),
            'ends_at' => Carbon::now()->subDays(2)->addHours(2),
            'attendance_opens_at' => Carbon::now()->subDays(2),
            'attendance_closes_at' => Carbon::now()->subDays(2)->addHours(2),
            'allowed_methods' => ['qr_code'],
            'status' => 'ditutup',
        ]);

        // Student 1: Session 1 => hadir, Session 2 => terlambat
        // Total sessions = 2, Hadir = 1, Terlambat = 1, Present = 2 (100.0%)
        AttendanceRecord::query()->create([
            'attendance_session_id' => $session1->id,
            'student_id' => $student1->id,
            'status' => 'hadir',
            'method' => 'manual',
        ]);
        AttendanceRecord::query()->create([
            'attendance_session_id' => $session2->id,
            'student_id' => $student1->id,
            'status' => 'terlambat',
            'method' => 'manual',
        ]);

        // Student 2: Session 1 => izin, Session 2 => unrecorded
        // Total sessions = 2, Hadir = 0, Izin = 1, Belum Dicatat = 1, Present = 0 (0.0%)
        AttendanceRecord::query()->create([
            'attendance_session_id' => $session1->id,
            'student_id' => $student2->id,
            'status' => 'izin',
            'method' => 'manual',
        ]);

        $response = $this->actingAs($this->admin)->get(route('course-sections.attendance-recap', $this->courseSection));

        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertSee('Bob');
        $response->assertSee('100%');
        $response->assertSee('0%');
    }

    public function test_recap_does_not_count_scheduled_open_or_cancelled_sessions_in_denominator(): void
    {
        $student = User::factory()->create(['name' => 'Charlie', 'role' => 'mahasiswa', 'is_active' => true]);
        Enrollment::query()->create([
            'course_section_id' => $this->courseSection->id,
            'student_id' => $student->id,
            'status' => 'aktif',
        ]);

        // 1 closed session
        $closedSession = AttendanceSession::query()->create([
            'course_section_id' => $this->courseSection->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan 1',
            'starts_at' => Carbon::now()->subDays(7),
            'ends_at' => Carbon::now()->subDays(7)->addHours(2),
            'attendance_opens_at' => Carbon::now()->subDays(7),
            'attendance_closes_at' => Carbon::now()->subDays(7)->addHours(2),
            'allowed_methods' => ['qr_code'],
            'status' => 'ditutup',
        ]);

        // 1 scheduled session (terjadwal)
        AttendanceSession::query()->create([
            'course_section_id' => $this->courseSection->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan 2',
            'starts_at' => Carbon::now()->addDays(2),
            'ends_at' => Carbon::now()->addDays(2)->addHours(2),
            'attendance_opens_at' => Carbon::now()->addDays(2),
            'attendance_closes_at' => Carbon::now()->addDays(2)->addHours(2),
            'allowed_methods' => ['qr_code'],
            'status' => 'terjadwal',
        ]);

        // 1 open session (dibuka)
        AttendanceSession::query()->create([
            'course_section_id' => $this->courseSection->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan 3',
            'starts_at' => Carbon::now(),
            'ends_at' => Carbon::now()->addHours(2),
            'attendance_opens_at' => Carbon::now(),
            'attendance_closes_at' => Carbon::now()->addHours(2),
            'allowed_methods' => ['qr_code'],
            'status' => 'dibuka',
        ]);

        // 1 cancelled session (dibatalkan)
        AttendanceSession::query()->create([
            'course_section_id' => $this->courseSection->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan 4',
            'starts_at' => Carbon::now()->subDays(3),
            'ends_at' => Carbon::now()->subDays(3)->addHours(2),
            'attendance_opens_at' => Carbon::now()->subDays(3),
            'attendance_closes_at' => Carbon::now()->subDays(3)->addHours(2),
            'allowed_methods' => ['qr_code'],
            'status' => 'dibatalkan',
            'cancelled_at' => Carbon::now(),
        ]);

        AttendanceRecord::query()->create([
            'attendance_session_id' => $closedSession->id,
            'student_id' => $student->id,
            'status' => 'hadir',
            'method' => 'manual',
        ]);

        $recapService = app(AttendanceRecapService::class);
        $recap = $recapService->getRecap($this->courseSection);

        // Denominator should only be 1
        $this->assertEquals(1, $recap['totalQualifyingSessions']);
        $studentRecap = $recap['studentRecaps']->first();
        $this->assertEquals(1, $studentRecap['total_sessions']);
        $this->assertEquals(1, $studentRecap['hadir']);
        $this->assertEquals(100.0, $studentRecap['percentage']);
    }

    public function test_recap_does_not_double_count_replaced_original_session(): void
    {
        $student = User::factory()->create(['name' => 'David', 'role' => 'mahasiswa', 'is_active' => true]);
        Enrollment::query()->create([
            'course_section_id' => $this->courseSection->id,
            'student_id' => $student->id,
            'status' => 'aktif',
        ]);

        // Original session was closed (e.g. earlier) or replaced
        $originalSession = AttendanceSession::query()->create([
            'course_section_id' => $this->courseSection->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan Asli',
            'starts_at' => Carbon::now()->subDays(10),
            'ends_at' => Carbon::now()->subDays(10)->addHours(2),
            'attendance_opens_at' => Carbon::now()->subDays(10),
            'attendance_closes_at' => Carbon::now()->subDays(10)->addHours(2),
            'allowed_methods' => ['qr_code'],
            'status' => 'ditutup',
        ]);

        // Replacement session that replaced the original session and is closed
        $replacementSession = AttendanceSession::query()->create([
            'course_section_id' => $this->courseSection->id,
            'session_type' => 'pengganti_ats',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan Pengganti ATS',
            'starts_at' => Carbon::now()->subDays(5),
            'ends_at' => Carbon::now()->subDays(5)->addHours(2),
            'attendance_opens_at' => Carbon::now()->subDays(5),
            'attendance_closes_at' => Carbon::now()->subDays(5)->addHours(2),
            'allowed_methods' => ['qr_code'],
            'status' => 'ditutup',
            'original_session_id' => $originalSession->id,
        ]);

        AttendanceRecord::query()->create([
            'attendance_session_id' => $replacementSession->id,
            'student_id' => $student->id,
            'status' => 'hadir',
            'method' => 'manual',
        ]);

        $recapService = app(AttendanceRecapService::class);
        $recap = $recapService->getRecap($this->courseSection);

        // Even though both sessions have status = 'ditutup', original is excluded so total is 1
        $this->assertEquals(1, $recap['totalQualifyingSessions']);
        $studentRecap = $recap['studentRecaps']->first();
        $this->assertEquals(1, $studentRecap['total_sessions']);
        $this->assertEquals(1, $studentRecap['hadir']);
        $this->assertEquals(100.0, $studentRecap['percentage']);
    }

    public function test_recap_handles_zero_qualifying_sessions_gracefully(): void
    {
        $student = User::factory()->create(['name' => 'Eva', 'role' => 'mahasiswa', 'is_active' => true]);
        Enrollment::query()->create([
            'course_section_id' => $this->courseSection->id,
            'student_id' => $student->id,
            'status' => 'aktif',
        ]);

        $recapService = app(AttendanceRecapService::class);
        $recap = $recapService->getRecap($this->courseSection);

        $this->assertEquals(0, $recap['totalQualifyingSessions']);
        $this->assertNull($recap['overallPercentage']);

        $studentRecap = $recap['studentRecaps']->first();
        $this->assertEquals(0, $studentRecap['total_sessions']);
        $this->assertNull($studentRecap['percentage']);

        $response = $this->actingAs($this->admin)->get(route('course-sections.attendance-recap', $this->courseSection));
        $response->assertOk();
        $response->assertSee('—');
    }

    public function test_inactive_enrollments_or_inactive_students_are_excluded(): void
    {
        $activeStudent = User::factory()->create(['name' => 'Active Student', 'role' => 'mahasiswa', 'is_active' => true]);
        $inactiveStudent = User::factory()->create(['name' => 'Inactive Student', 'role' => 'mahasiswa', 'is_active' => false]);
        $droppedStudent = User::factory()->create(['name' => 'Dropped Student', 'role' => 'mahasiswa', 'is_active' => true]);

        Enrollment::query()->create([
            'course_section_id' => $this->courseSection->id,
            'student_id' => $activeStudent->id,
            'status' => 'aktif',
        ]);
        Enrollment::query()->create([
            'course_section_id' => $this->courseSection->id,
            'student_id' => $inactiveStudent->id,
            'status' => 'aktif',
        ]);
        Enrollment::query()->create([
            'course_section_id' => $this->courseSection->id,
            'student_id' => $droppedStudent->id,
            'status' => 'nonaktif',
        ]);

        $recapService = app(AttendanceRecapService::class);
        $recap = $recapService->getRecap($this->courseSection);

        $this->assertEquals(1, $recap['totalActiveStudents']);
        $this->assertEquals('Active Student', $recap['studentRecaps']->first()['name']);
    }
}

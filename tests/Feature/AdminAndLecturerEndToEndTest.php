<?php

namespace Tests\Feature;

use App\Enums\UserRole;
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

class AdminAndLecturerEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_lecturer_full_session_lifecycle_keeps_dashboard_session_detail_and_recap_consistent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-05 08:30', 'Asia/Jakarta'));

        $section = $this->createSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value, 'must_change_password' => false]);
        CourseSectionLecturer::query()->create([
            'course_section_id' => $section->id,
            'user_id' => $lecturer->id,
            'teaching_type' => 'teori',
        ]);
        $students = User::factory()->count(3)->create(['role' => UserRole::Mahasiswa->value]);
        foreach ($students as $student) {
            Enrollment::query()->create([
                'course_section_id' => $section->id,
                'student_id' => $student->id,
                'status' => 'aktif',
            ]);
        }

        $payload = [
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan E2E',
            'starts_at' => '2026-11-05 08:00',
            'ends_at' => '2026-11-05 10:00',
            'attendance_opens_at' => '2026-11-05 07:45',
            'attendance_closes_at' => '2026-11-05 10:15',
            'allowed_methods' => ['manual'],
        ];

        $this->actingAs($lecturer)
            ->post(route('course-sections.attendance-sessions.store', $section), $payload)
            ->assertRedirect();

        $session = AttendanceSession::query()->where('title', 'Pertemuan E2E')->firstOrFail();

        $this->actingAs($lecturer)
            ->post(route('course-sections.attendance-sessions.open', [$section, $session]))
            ->assertSessionHasNoErrors();
        $this->assertSame('terbuka', $session->fresh()->status);

        $this->actingAs($lecturer)->post(
            route('course-sections.attendance-sessions.records.store', [$section, $session]),
            ['student_id' => $students[0]->id, 'status' => 'hadir']
        )->assertSessionHasNoErrors();
        $this->actingAs($lecturer)->post(
            route('course-sections.attendance-sessions.records.store', [$section, $session]),
            ['student_id' => $students[1]->id, 'status' => 'terlambat']
        )->assertSessionHasNoErrors();

        $this->actingAs($lecturer)
            ->post(route('course-sections.attendance-sessions.close', [$section, $session]))
            ->assertSessionHasNoErrors();

        $statuses = AttendanceRecord::query()
            ->where('attendance_session_id', $session->id)
            ->pluck('status', 'student_id');

        $this->assertSame('hadir', $statuses[$students[0]->id]);
        $this->assertSame('terlambat', $statuses[$students[1]->id]);
        $this->assertSame('tidak_hadir', $statuses[$students[2]->id]);

        $recap = app(AttendanceRecapService::class)->getRecap($section);
        $this->assertSame(1, $recap['totalQualifyingSessions']);
        $this->assertEqualsWithDelta(66.7, (float) $recap['overallPercentage'], 0.1);

        $this->actingAs($lecturer)
            ->get(route('course-sections.attendance-sessions.show', [$section, $session]))
            ->assertOk()
            ->assertSeeText('66.7%');

        $this->actingAs($lecturer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-statistic="attendance-total"', false)
            ->assertSeeText('Pertemuan E2E');
    }

    public function test_cancelled_session_is_not_counted_and_lecturer_cannot_open_other_lecturers_section(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-05 08:30', 'Asia/Jakarta'));

        $section = $this->createSection();
        $otherSection = $this->createSection('Kelas Lain');
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value, 'must_change_password' => false]);
        CourseSectionLecturer::query()->create([
            'course_section_id' => $section->id,
            'user_id' => $lecturer->id,
            'teaching_type' => 'teori',
        ]);

        $session = AttendanceSession::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Sesi Batal',
            'starts_at' => '2026-11-05 08:00:00',
            'ends_at' => '2026-11-05 10:00:00',
            'attendance_opens_at' => '2026-11-05 07:45:00',
            'attendance_closes_at' => '2026-11-05 10:15:00',
            'allowed_methods' => ['manual'],
            'status' => 'terjadwal',
        ]);

        $this->actingAs($lecturer)
            ->post(route('course-sections.attendance-sessions.cancel', [$section, $session]), [
                'cancellation_reason' => 'Dosen berhalangan',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotNull($session->fresh()->cancelled_at);
        $this->assertSame(0, app(AttendanceRecapService::class)->getRecap($section)['totalQualifyingSessions']);

        $this->actingAs($lecturer)
            ->get(route('course-sections.attendance-sessions.index', $otherSection))
            ->assertForbidden();
    }

    private function createSection(string $name = 'Kelas A'): CourseSection
    {
        $course = Course::query()->create([
            'code' => 'E2E'.fake()->unique()->numerify('###'),
            'name' => 'Mata Kuliah '.fake()->unique()->numerify('###'),
        ]);

        $period = AcademicPeriod::query()->firstOrCreate(
            ['name' => 'Semester Ganjil 2026/2027'],
            [
                'academic_year' => '2026/2027',
                'semester' => 'ganjil',
                'starts_on' => '2026-08-01',
                'ends_on' => '2027-01-31',
                'ats_starts_on' => '2026-10-05',
                'ats_ends_on' => '2026-10-16',
                'uas_starts_on' => '2027-01-11',
                'uas_ends_on' => '2027-01-22',
                'replacement_deadline' => '2027-01-30',
                'is_active' => true,
            ]
        );

        return CourseSection::query()->create([
            'course_id' => $course->id,
            'academic_period_id' => $period->id,
            'name' => $name,
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
    }
}

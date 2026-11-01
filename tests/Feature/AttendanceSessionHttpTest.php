<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AcademicPeriod;
use App\Models\AttendanceSession;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CourseSectionLecturer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSessionHttpTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_attendance_session_routes(): void
    {
        $section = $this->createCourseSection();
        $session = $this->createAttendanceSession($section);

        $this->get(route('course-sections.attendance-sessions.index', $section))->assertRedirectToRoute('login');
        $this->get(route('course-sections.attendance-sessions.create', $section))->assertRedirectToRoute('login');
        $this->post(route('course-sections.attendance-sessions.store', $section), [])->assertRedirectToRoute('login');
        $this->get(route('course-sections.attendance-sessions.show', [$section, $session]))->assertRedirectToRoute('login');
        $this->get(route('course-sections.attendance-sessions.replacement.create', [$section, $session]))->assertRedirectToRoute('login');
        $this->post(route('course-sections.attendance-sessions.open', [$section, $session]))->assertRedirectToRoute('login');
        $this->post(route('course-sections.attendance-sessions.close', [$section, $session]))->assertRedirectToRoute('login');
        $this->post(route('course-sections.attendance-sessions.cancel', [$section, $session]))->assertRedirectToRoute('login');
        $this->post(route('course-sections.attendance-sessions.replacement', [$section, $session]))->assertRedirectToRoute('login');
    }

    public function test_student_cannot_access_attendance_session_routes(): void
    {
        $section = $this->createCourseSection();
        $session = $this->createAttendanceSession($section);
        $student = User::factory()->create(['role' => UserRole::Mahasiswa->value]);

        $this->actingAs($student)->get(route('course-sections.attendance-sessions.index', $section))->assertForbidden();
        $this->actingAs($student)->get(route('course-sections.attendance-sessions.create', $section))->assertForbidden();
        $this->actingAs($student)->post(route('course-sections.attendance-sessions.store', $section), [])->assertForbidden();
        $this->actingAs($student)->get(route('course-sections.attendance-sessions.show', [$section, $session]))->assertForbidden();
        $this->actingAs($student)->get(route('course-sections.attendance-sessions.replacement.create', [$section, $session]))->assertForbidden();
        $this->actingAs($student)->post(route('course-sections.attendance-sessions.open', [$section, $session]))->assertForbidden();
        $this->actingAs($student)->post(route('course-sections.attendance-sessions.close', [$section, $session]))->assertForbidden();
        $this->actingAs($student)->post(route('course-sections.attendance-sessions.cancel', [$section, $session]))->assertForbidden();
        $this->actingAs($student)->post(route('course-sections.attendance-sessions.replacement', [$section, $session]))->assertForbidden();
    }

    public function test_unassigned_lecturer_cannot_manage_sessions_for_section(): void
    {
        $section = $this->createCourseSection();
        $session = $this->createAttendanceSession($section);
        $unassignedLecturer = User::factory()->create(['role' => UserRole::Dosen->value]);

        $this->actingAs($unassignedLecturer)->get(route('course-sections.attendance-sessions.index', $section))->assertForbidden();
        $this->actingAs($unassignedLecturer)->get(route('course-sections.attendance-sessions.create', $section))->assertForbidden();
        $this->actingAs($unassignedLecturer)->post(route('course-sections.attendance-sessions.store', $section), [])->assertForbidden();
        $this->actingAs($unassignedLecturer)->get(route('course-sections.attendance-sessions.show', [$section, $session]))->assertForbidden();
        $this->actingAs($unassignedLecturer)->get(route('course-sections.attendance-sessions.replacement.create', [$section, $session]))->assertForbidden();
        $this->actingAs($unassignedLecturer)->post(route('course-sections.attendance-sessions.open', [$section, $session]))->assertForbidden();
        $this->actingAs($unassignedLecturer)->post(route('course-sections.attendance-sessions.close', [$section, $session]))->assertForbidden();
        $this->actingAs($unassignedLecturer)->post(route('course-sections.attendance-sessions.cancel', [$section, $session]))->assertForbidden();
        $this->actingAs($unassignedLecturer)->post(route('course-sections.attendance-sessions.replacement', [$section, $session]))->assertForbidden();
    }

    public function test_admin_and_assigned_lecturer_can_view_session_index_and_show(): void
    {
        $section = $this->createCourseSection();
        $session = $this->createAttendanceSession($section, ['title' => 'Pertemuan 1 Pengantar']);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $this->actingAs($admin)->get(route('course-sections.attendance-sessions.index', $section))
            ->assertOk()
            ->assertSeeText('Pertemuan 1 Pengantar')
            ->assertSee('Buat Sesi Reguler');
        $this->actingAs($admin)->get(route('course-sections.attendance-sessions.create', $section))
            ->assertOk()
            ->assertSee('Buat Sesi Reguler')
            ->assertSeeText('1. Informasi Sesi')
            ->assertSeeText('2. Waktu Perkuliahan')
            ->assertSeeText('3. Waktu Presensi')
            ->assertSeeText('4. Pengaturan Presensi');
        $this->actingAs($admin)->get(route('course-sections.attendance-sessions.show', [$section, $session]))
            ->assertOk()
            ->assertSeeText('Pertemuan 1 Pengantar')
            ->assertSee('Buat Sesi Pengganti');
        $this->actingAs($admin)->get(route('course-sections.attendance-sessions.replacement.create', [$section, $session]))
            ->assertOk()
            ->assertSee('Buat Sesi Pengganti');

        $this->actingAs($lecturer)->get(route('course-sections.attendance-sessions.index', $section))
            ->assertOk()
            ->assertSeeText('Pertemuan 1 Pengantar')
            ->assertSee('Buat Sesi Reguler');
        $this->actingAs($lecturer)->get(route('course-sections.attendance-sessions.create', $section))
            ->assertOk()
            ->assertSee('Buat Sesi Reguler');
        $this->actingAs($lecturer)->get(route('course-sections.attendance-sessions.show', [$section, $session]))
            ->assertOk()
            ->assertSeeText('Pertemuan 1 Pengantar')
            ->assertSee('Buat Sesi Pengganti');
        $this->actingAs($lecturer)->get(route('course-sections.attendance-sessions.replacement.create', [$section, $session]))
            ->assertOk()
            ->assertSee('Buat Sesi Pengganti');
    }

    public function test_assigned_lecturer_can_store_regular_session(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $schedule = ClassSchedule::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'day_of_week' => 1, // Senin
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'timezone' => 'Asia/Jakarta',
            'location' => 'Lab 1',
            'is_active' => true,
        ]);

        // 2026-11-02 adalah hari Senin (isoWeekday = 1)
        $payload = [
            'class_schedule_id' => $schedule->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan Reguler 1',
            'starts_at' => '2026-11-02 08:00',
            'ends_at' => '2026-11-02 10:00',
            'attendance_opens_at' => '2026-11-02 07:45',
            'attendance_closes_at' => '2026-11-02 10:15',
            'allowed_methods' => ['qr_code'],
            'late_after_minutes' => 15,
        ];

        $response = $this->actingAs($lecturer)->post(route('course-sections.attendance-sessions.store', $section), $payload);
        $response->assertRedirect();

        $this->assertDatabaseHas('attendance_sessions', [
            'course_section_id' => $section->id,
            'class_schedule_id' => $schedule->id,
            'title' => 'Pertemuan Reguler 1',
            'session_kind' => 'regular',
            'status' => 'terjadwal',
        ]);
    }

    public function test_regular_session_validation_fails_when_day_of_week_does_not_match_schedule(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $schedule = ClassSchedule::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'day_of_week' => 1, // Senin
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'timezone' => 'Asia/Jakarta',
            'location' => 'Lab 1',
            'is_active' => true,
        ]);

        // 2026-11-03 adalah hari Selasa (isoWeekday = 2)
        $payload = [
            'class_schedule_id' => $schedule->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan Hari Salah',
            'starts_at' => '2026-11-03 08:00',
            'ends_at' => '2026-11-03 10:00',
            'attendance_opens_at' => '2026-11-03 07:45',
            'attendance_closes_at' => '2026-11-03 10:15',
            'allowed_methods' => ['qr_code'],
        ];

        $response = $this->actingAs($lecturer)->post(route('course-sections.attendance-sessions.store', $section), $payload);
        $response->assertSessionHasErrors('starts_at');
        $this->assertDatabaseMissing('attendance_sessions', [
            'title' => 'Pertemuan Hari Salah',
        ]);
    }

    public function test_opening_session_via_http(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $now = Carbon::now('Asia/Jakarta');
        $session = $this->createAttendanceSession($section, [
            'status' => 'terjadwal',
            'attendance_opens_at' => $now->copy()->subMinutes(5)->format('Y-m-d H:i:s'),
            'attendance_closes_at' => $now->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
        ]);

        $response = $this->actingAs($lecturer)->post(route('course-sections.attendance-sessions.open', [$section, $session]));
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Sesi absensi berhasil dibuka.');

        $session->refresh();
        $this->assertSame('terbuka', $session->status);
        $this->assertSame($lecturer->id, $session->opened_by);
    }

    public function test_opening_session_before_allowed_time_returns_validation_error_not_500(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $now = Carbon::now('Asia/Jakarta');
        $session = $this->createAttendanceSession($section, [
            'status' => 'terjadwal',
            'attendance_opens_at' => $now->copy()->addMinutes(30)->format('Y-m-d H:i:s'),
            'attendance_closes_at' => $now->copy()->addHours(2)->format('Y-m-d H:i:s'),
        ]);

        $response = $this->actingAs($lecturer)->post(route('course-sections.attendance-sessions.open', [$section, $session]));
        $response->assertRedirect();
        $response->assertSessionHasErrors('session');

        $session->refresh();
        $this->assertSame('terjadwal', $session->status);
    }

    public function test_closing_session_via_http(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $session = $this->createAttendanceSession($section, [
            'status' => 'terbuka',
            'attendance_opens_at' => '2026-11-05 07:45:00',
            'attendance_closes_at' => '2026-11-05 10:15:00',
        ]);

        $response = $this->actingAs($lecturer)->post(route('course-sections.attendance-sessions.close', [$section, $session]));
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Sesi absensi berhasil ditutup.');

        $session->refresh();
        $this->assertSame('ditutup', $session->status);
        $this->assertSame($lecturer->id, $session->closed_by);
    }

    public function test_cancelling_session_via_http(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $session = $this->createAttendanceSession($section, [
            'status' => 'terjadwal',
            'attendance_opens_at' => '2026-11-05 07:45:00',
            'attendance_closes_at' => '2026-11-05 10:15:00',
        ]);

        $response = $this->actingAs($lecturer)->post(route('course-sections.attendance-sessions.cancel', [$section, $session]), [
            'cancellation_reason' => 'Dosen ada rapat dinas luar kota',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Sesi absensi berhasil dibatalkan.');

        $session->refresh();
        $this->assertSame('Dosen ada rapat dinas luar kota', $session->cancellation_reason);
        $this->assertSame($lecturer->id, $session->cancelled_by);
        $this->assertNotNull($session->cancelled_at);
    }

    public function test_storing_replacement_session_via_http(): void
    {
        $period = AcademicPeriod::query()->create([
            'name' => 'Semester Ganjil 2026/2027',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'starts_on' => '2026-08-01',
            'ends_on' => '2027-01-31',
            'ats_starts_on' => '2026-10-05',
            'ats_ends_on' => '2026-10-16',
            'uas_starts_on' => '2027-01-11',
            'uas_ends_on' => '2027-01-22',
            'ats_replacement_starts_on' => '2026-10-20',
            'ats_replacement_ends_on' => '2026-10-25',
            'uas_replacement_starts_on' => '2027-01-25',
            'uas_replacement_ends_on' => '2027-01-30',
            'replacement_deadline' => '2027-01-30',
            'is_active' => true,
        ]);

        $section = $this->createCourseSection('Kelas Pengganti', $period);
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $original = $this->createAttendanceSession($section, ['title' => 'Pertemuan Asli']);

        $payload = [
            'replacement_category' => 'ats',
            'session_type' => 'teori',
            'delivery_mode' => 'online',
            'title' => 'Pertemuan Pengganti ATS',
            'starts_at' => '2026-10-22 13:00',
            'ends_at' => '2026-10-22 15:00',
            'attendance_opens_at' => '2026-10-22 12:45',
            'attendance_closes_at' => '2026-10-22 15:15',
            'allowed_methods' => ['qr_code'],
        ];

        $response = $this->actingAs($lecturer)->post(
            route('course-sections.attendance-sessions.replacement', [$section, $original]),
            $payload
        );

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Sesi pengganti berhasil dibuat.');

        $this->assertDatabaseHas('attendance_sessions', [
            'course_section_id' => $section->id,
            'original_session_id' => $original->id,
            'title' => 'Pertemuan Pengganti ATS',
            'session_kind' => 'replacement',
            'replacement_category' => 'ats',
        ]);
    }

    public function test_storing_replacement_session_with_date_outside_period_returns_error_not_500(): void
    {
        $period = AcademicPeriod::query()->create([
            'name' => 'Semester Ganjil 2026/2027',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'starts_on' => '2026-08-01',
            'ends_on' => '2027-01-31',
            'ats_starts_on' => '2026-10-05',
            'ats_ends_on' => '2026-10-16',
            'uas_starts_on' => '2027-01-11',
            'uas_ends_on' => '2027-01-22',
            'ats_replacement_starts_on' => '2026-10-20',
            'ats_replacement_ends_on' => '2026-10-25',
            'replacement_deadline' => '2027-01-30',
            'is_active' => true,
        ]);

        $section = $this->createCourseSection('Kelas Pengganti', $period);
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $original = $this->createAttendanceSession($section, ['title' => 'Pertemuan Asli']);

        $payload = [
            'replacement_category' => 'ats',
            'session_type' => 'teori',
            'delivery_mode' => 'online',
            'title' => 'Pertemuan Pengganti Tanggal Salah',
            'starts_at' => '2026-10-28 13:00', // di luar 20 s/d 25
            'ends_at' => '2026-10-28 15:00',
            'attendance_opens_at' => '2026-10-28 12:45',
            'attendance_closes_at' => '2026-10-28 15:15',
            'allowed_methods' => ['qr_code'],
        ];

        $response = $this->actingAs($lecturer)->post(
            route('course-sections.attendance-sessions.replacement', [$section, $original]),
            $payload
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors('replacement_category');
        $this->assertDatabaseMissing('attendance_sessions', [
            'title' => 'Pertemuan Pengganti Tanggal Salah',
        ]);
    }

    private function createCourseSection(string $name = 'Kelas A', ?AcademicPeriod $period = null): CourseSection
    {
        $course = Course::query()->create([
            'code' => 'CS'.fake()->unique()->numerify('###'),
            'name' => 'Mata Kuliah '.fake()->unique()->numerify('###'),
        ]);

        if (! $period) {
            $period = AcademicPeriod::query()->create([
                'name' => 'Semester Ganjil 2026/2027',
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
            ]);
        }

        return CourseSection::query()->create([
            'course_id' => $course->id,
            'academic_period_id' => $period->id,
            'name' => $name,
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
    }

    private function createAttendanceSession(CourseSection $section, array $attributes = []): AttendanceSession
    {
        return AttendanceSession::query()->create(array_merge([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan 1',
            'starts_at' => '2026-11-05 08:00:00',
            'ends_at' => '2026-11-05 10:00:00',
            'attendance_opens_at' => '2026-11-05 07:45:00',
            'attendance_closes_at' => '2026-11-05 10:15:00',
            'allowed_methods' => ['qr_code'],
            'status' => 'terjadwal',
        ], $attributes));
    }

    private function assignLecturer(CourseSection $section, User $lecturer, string $type = 'teori'): CourseSectionLecturer
    {
        return CourseSectionLecturer::query()->create([
            'course_section_id' => $section->id,
            'user_id' => $lecturer->id,
            'teaching_type' => $type,
        ]);
    }
}

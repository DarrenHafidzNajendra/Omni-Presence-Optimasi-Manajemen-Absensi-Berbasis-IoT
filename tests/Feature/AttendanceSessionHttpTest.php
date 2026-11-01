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

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

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
        Carbon::setTestNow(Carbon::parse('2026-11-02 08:05:00', 'Asia/Jakarta'));

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

    public function test_lecturer_cannot_open_or_create_regular_session_before_schedule_starts(): void
    {
        // Waktu sekarang pukul 07:30, sedangkan jadwal mulai pukul 08:00
        Carbon::setTestNow(Carbon::parse('2026-11-02 07:30:00', 'Asia/Jakarta'));

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

        // 1. Melalui tombol Buka Presensi langsung dari jadwal
        $openFromScheduleResponse = $this->actingAs($lecturer)
            ->post(route('course-sections.schedules.open-session', [$section, $schedule]));
        $openFromScheduleResponse->assertRedirect();
        $openFromScheduleResponse->assertSessionHasErrors('session');

        // 2. Melalui form store dengan class_schedule_id
        $storeResponse = $this->actingAs($lecturer)->post(route('course-sections.attendance-sessions.store', $section), [
            'class_schedule_id' => $schedule->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan Sebelum Jadwal',
            'starts_at' => '2026-11-02 08:00',
            'ends_at' => '2026-11-02 10:00',
            'attendance_opens_at' => '2026-11-02 08:00',
            'attendance_closes_at' => '2026-11-02 10:00',
            'allowed_methods' => ['qr_code'],
        ]);
        $storeResponse->assertRedirect();
        $storeResponse->assertSessionHasErrors('starts_at');

        $this->assertDatabaseCount('attendance_sessions', 0);
    }

    public function test_lecturer_can_open_session_from_schedule_when_start_time_is_reached_and_repeated_requests_do_not_duplicate(): void
    {
        // Waktu sekarang pukul 08:05 (Senin, 2026-11-02), jadwal mulai pukul 08:00
        Carbon::setTestNow(Carbon::parse('2026-11-02 08:05:00', 'Asia/Jakarta'));

        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $schedule = ClassSchedule::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'praktikum',
            'delivery_mode' => 'onsite',
            'day_of_week' => 1, // Senin
            'starts_at' => '08:00',
            'ends_at' => '10:30',
            'timezone' => 'Asia/Jakarta',
            'location' => 'Lab IoT 2',
            'is_active' => true,
        ]);

        // Halaman index menampilkan tombol Buka Presensi
        $this->actingAs($lecturer)
            ->get(route('course-sections.attendance-sessions.index', $section))
            ->assertOk()
            ->assertSeeText('Buka Presensi');

        // Permintaan pertama membuat dan membuka sesi
        $firstResponse = $this->actingAs($lecturer)
            ->post(route('course-sections.schedules.open-session', [$section, $schedule]));
        $firstResponse->assertRedirect();
        $firstResponse->assertSessionHas('success', 'Sesi presensi berhasil dibuka.');

        $this->assertDatabaseCount('attendance_sessions', 1);

        $session = AttendanceSession::query()->firstOrFail();
        $this->assertSame($section->id, $session->course_section_id);
        $this->assertSame($schedule->id, $session->class_schedule_id);
        $this->assertSame('praktikum', $session->session_type);
        $this->assertSame('onsite', $session->delivery_mode);
        $this->assertSame('terbuka', $session->status);
        $this->assertSame($lecturer->id, $session->opened_by);
        $this->assertSame('2026-11-02 08:00:00', $session->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-02 10:30:00', $session->ends_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-11-03 08:00:00', $session->attendance_closes_at->format('Y-m-d H:i:s'));

        // Permintaan kedua (berulang) ditolak dan tidak membuat sesi ganda
        $secondResponse = $this->actingAs($lecturer)
            ->post(route('course-sections.schedules.open-session', [$section, $schedule]));
        $secondResponse->assertRedirect();
        $secondResponse->assertSessionHasErrors('session');

        $this->assertDatabaseCount('attendance_sessions', 1);
    }

    public function test_unassigned_lecturer_cannot_open_session_from_another_lecturers_schedule(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 08:05:00', 'Asia/Jakarta'));

        $section = $this->createCourseSection();
        $unassignedLecturer = User::factory()->create(['role' => UserRole::Dosen->value]);

        $schedule = ClassSchedule::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'timezone' => 'Asia/Jakarta',
            'location' => 'Lab 1',
            'is_active' => true,
        ]);

        $this->actingAs($unassignedLecturer)
            ->post(route('course-sections.schedules.open-session', [$section, $schedule]))
            ->assertForbidden();

        $this->assertDatabaseCount('attendance_sessions', 0);
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

    public function test_next_regular_meeting_is_automatically_available_on_subsequent_week_without_recreating_schedule(): void
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
            'location' => 'Lab IoT 1',
            'is_active' => true,
        ]);

        // Minggu 1: Senin, 2026-11-02 pukul 08:10 -> Buka dan tutup sesi
        Carbon::setTestNow(Carbon::parse('2026-11-02 08:10:00', 'Asia/Jakarta'));

        $this->actingAs($lecturer)
            ->post(route('course-sections.schedules.open-session', [$section, $schedule]))
            ->assertRedirect();

        $weekOneSession = AttendanceSession::query()->firstOrFail();
        $this->assertSame('Pertemuan 1', $weekOneSession->title);
        $this->assertSame('2026-11-02 08:00:00', $weekOneSession->starts_at->format('Y-m-d H:i:s'));

        $this->actingAs($lecturer)
            ->post(route('course-sections.attendance-sessions.close', [$section, $weekOneSession]))
            ->assertRedirect();

        // Minggu 2: Senin, 2026-11-09 pukul 07:30 (sebelum jam 08:00) -> tombol Buka Presensi belum muncul
        Carbon::setTestNow(Carbon::parse('2026-11-09 07:30:00', 'Asia/Jakarta'));

        $this->actingAs($lecturer)
            ->get(route('course-sections.attendance-sessions.index', $section))
            ->assertOk()
            ->assertDontSeeText('Buka Presensi')
            ->assertSeeText('Belum dimulai (Mulai pukul 08:00 WIB)');

        // Minggu 2: Senin, 2026-11-09 pukul 08:05 (waktu mulai tercapai) -> tombol Buka Presensi muncul otomatis
        Carbon::setTestNow(Carbon::parse('2026-11-09 08:05:00', 'Asia/Jakarta'));

        $this->actingAs($lecturer)
            ->get(route('course-sections.attendance-sessions.index', $section))
            ->assertOk()
            ->assertSeeText('Buka Presensi');

        $this->actingAs($lecturer)
            ->post(route('course-sections.schedules.open-session', [$section, $schedule]))
            ->assertRedirect()
            ->assertSessionHas('success', 'Sesi presensi berhasil dibuka.');

        $this->assertDatabaseCount('attendance_sessions', 2);

        $weekTwoSession = AttendanceSession::query()->orderByDesc('id')->firstOrFail();
        $this->assertSame('Pertemuan 2', $weekTwoSession->title);
        $this->assertSame('terbuka', $weekTwoSession->status);
        $this->assertSame('2026-11-09 08:00:00', $weekTwoSession->starts_at->format('Y-m-d H:i:s'));
    }

    public function test_session_detail_and_index_ui_hide_open_and_reopen_buttons_for_closed_cancelled_expired_and_future_sessions(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 09:00:00', 'Asia/Jakarta'));

        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $closedSession = $this->createAttendanceSession($section, [
            'title' => 'Pertemuan Ditutup',
            'status' => 'ditutup',
            'starts_at' => '2026-10-26 08:00:00',
            'ends_at' => '2026-10-26 10:00:00',
            'attendance_opens_at' => '2026-10-26 08:00:00',
            'attendance_closes_at' => '2026-10-27 08:00:00',
        ]);

        $cancelledSession = $this->createAttendanceSession($section, [
            'title' => 'Pertemuan Batal',
            'status' => 'terjadwal',
            'starts_at' => '2026-11-02 08:00:00',
            'ends_at' => '2026-11-02 10:00:00',
            'attendance_opens_at' => '2026-11-02 08:00:00',
            'attendance_closes_at' => '2026-11-03 08:00:00',
        ]);
        $cancelledSession->cancel($lecturer, 'Dosen tugas luar');

        $expiredSession = $this->createAttendanceSession($section, [
            'title' => 'Pertemuan Kedaluwarsa',
            'status' => 'terjadwal',
            'starts_at' => '2026-10-19 08:00:00',
            'ends_at' => '2026-10-19 10:00:00',
            'attendance_opens_at' => '2026-10-19 08:00:00',
            'attendance_closes_at' => '2026-10-20 08:00:00',
        ]);

        $futureSession = $this->createAttendanceSession($section, [
            'title' => 'Pertemuan Masa Depan',
            'status' => 'terjadwal',
            'starts_at' => '2026-11-09 08:00:00',
            'ends_at' => '2026-11-09 10:00:00',
            'attendance_opens_at' => '2026-11-09 08:00:00',
            'attendance_closes_at' => '2026-11-10 08:00:00',
        ]);

        // Closed session detail page: shows 'ditutup', no 'Buka Sesi' or 'Batalkan Sesi'
        $this->actingAs($lecturer)
            ->get(route('course-sections.attendance-sessions.show', [$section, $closedSession]))
            ->assertOk()
            ->assertSeeText('ditutup')
            ->assertDontSeeText('Buka Sesi')
            ->assertDontSeeText('Batalkan Sesi');

        // Cancelled session detail page: shows 'dibatalkan', no 'Buka Sesi' or 'Batalkan Sesi'
        $this->actingAs($lecturer)
            ->get(route('course-sections.attendance-sessions.show', [$section, $cancelledSession]))
            ->assertOk()
            ->assertSeeText('dibatalkan')
            ->assertDontSeeText('Buka Sesi')
            ->assertDontSeeText('Batalkan Sesi');

        // Expired session detail page: shows 'kedaluwarsa', no 'Buka Sesi' or 'Batalkan Sesi'
        $this->actingAs($lecturer)
            ->get(route('course-sections.attendance-sessions.show', [$section, $expiredSession]))
            ->assertOk()
            ->assertSeeText('kedaluwarsa')
            ->assertDontSeeText('Buka Sesi')
            ->assertDontSeeText('Batalkan Sesi');

        // Future session detail page: shows 'terjadwal', has 'Batalkan Sesi' but no 'Buka Sesi' yet
        $this->actingAs($lecturer)
            ->get(route('course-sections.attendance-sessions.show', [$section, $futureSession]))
            ->assertOk()
            ->assertSeeText('terjadwal')
            ->assertDontSeeText('Buka Sesi')
            ->assertSeeText('Batalkan Sesi');
    }

    public function test_lecturer_session_index_orders_todays_schedule_first(): void
    {
        // Rabu, 2026-11-04 pukul 08:30 (isoWeekday = 3)
        Carbon::setTestNow(Carbon::parse('2026-11-04 08:30:00', 'Asia/Jakarta'));

        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        // Jadwal Senin (day_of_week = 1)
        ClassSchedule::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'timezone' => 'Asia/Jakarta',
            'location' => 'Ruang Senin',
            'is_active' => true,
        ]);

        // Jadwal Rabu / Hari Ini (day_of_week = 3)
        ClassSchedule::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'praktikum',
            'delivery_mode' => 'onsite',
            'day_of_week' => 3,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'timezone' => 'Asia/Jakarta',
            'location' => 'Ruang Rabu Hari Ini',
            'is_active' => true,
        ]);

        $this->actingAs($lecturer)
            ->get(route('course-sections.attendance-sessions.index', $section))
            ->assertOk()
            ->assertSeeTextInOrder(['Ruang Rabu Hari Ini', 'Ruang Senin'])
            ->assertSeeText('Jadwal Hari Ini')
            ->assertSeeText('Buka Presensi');
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

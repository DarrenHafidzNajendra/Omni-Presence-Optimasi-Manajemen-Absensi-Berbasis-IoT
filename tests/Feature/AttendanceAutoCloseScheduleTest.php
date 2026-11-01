<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Enrollment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class AttendanceAutoCloseScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_close_expired_sessions_task_is_registered_in_schedule(): void
    {
        $events = collect(Schedule::events());

        $matchingEvent = $events->first(function ($event): bool {
            return $event->description === 'attendance:close-expired-sessions'
                || str_contains((string) $event->description, 'close-expired-sessions');
        });

        $this->assertNotNull($matchingEvent, 'Schedule event attendance:close-expired-sessions is not registered.');
        $this->assertSame('*/5 * * * *', $matchingEvent->expression);
        $this->assertSame('Asia/Jakarta', $matchingEvent->timezone);
        $this->assertTrue($matchingEvent->withoutOverlapping);
    }

    public function test_schedule_run_automatically_closes_eligible_expired_sessions(): void
    {
        $course = Course::query()->create([
            'code' => 'CS'.fake()->unique()->numerify('###'),
            'name' => 'Pemrograman Web',
        ]);
        $section = CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $student = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        Enrollment::query()->create([
            'course_section_id' => $section->id,
            'student_id' => $student->id,
            'status' => 'aktif',
        ]);

        $now = Carbon::parse('2026-11-05 10:05:00', 'Asia/Jakarta');
        Carbon::setTestNow($now);

        $expiredSession = AttendanceSession::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Sesi Kedaluwarsa',
            'starts_at' => '2026-11-05 08:00:00',
            'ends_at' => '2026-11-05 10:00:00',
            'attendance_opens_at' => '2026-11-05 07:45:00',
            'attendance_closes_at' => '2026-11-05 10:00:00',
            'allowed_methods' => ['qr_code'],
            'status' => 'terbuka',
        ]);

        $stillActiveSession = AttendanceSession::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Sesi Masih Berjalan',
            'starts_at' => '2026-11-05 10:00:00',
            'ends_at' => '2026-11-05 12:00:00',
            'attendance_opens_at' => '2026-11-05 09:45:00',
            'attendance_closes_at' => '2026-11-05 12:00:00',
            'allowed_methods' => ['qr_code'],
            'status' => 'terbuka',
        ]);

        Artisan::call('schedule:run');

        $expiredSession->refresh();
        $this->assertSame('ditutup', $expiredSession->status);
        $this->assertNotNull($expiredSession->closed_at);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $expiredSession->id,
            'student_id' => $student->id,
            'status' => 'tidak_hadir',
        ]);

        $stillActiveSession->refresh();
        $this->assertSame('terbuka', $stillActiveSession->status);
        $this->assertNull($stillActiveSession->closed_at);

        Carbon::setTestNow();
    }

    public function test_open_and_unopened_scheduled_sessions_are_processed_after_24_hours_without_premature_closing(): void
    {
        $course = Course::query()->create([
            'code' => 'CS'.fake()->unique()->numerify('###'),
            'name' => 'Jaringan Sensor IoT',
        ]);
        $section = CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'B',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $presentStudent = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $absentStudent = User::factory()->create(['role' => UserRole::Mahasiswa->value]);

        Enrollment::query()->create([
            'course_section_id' => $section->id,
            'student_id' => $presentStudent->id,
            'status' => 'aktif',
        ]);
        Enrollment::query()->create([
            'course_section_id' => $section->id,
            'student_id' => $absentStudent->id,
            'status' => 'aktif',
        ]);

        // Sesi 1: Terbuka dari jadwal pukul 08:00 (batas maksimal 24 jam = besok pukul 08:00)
        $openSession = AttendanceSession::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Sesi Terbuka 24 Jam',
            'starts_at' => '2026-11-05 08:00:00',
            'ends_at' => '2026-11-05 10:00:00',
            'attendance_opens_at' => '2026-11-05 08:00:00',
            'attendance_closes_at' => '2026-11-07 08:00:00', // sengaja dibuat > 24 jam untuk menguji batas 24 jam
            'allowed_methods' => ['qr_code', 'face_recognition'],
            'status' => 'terbuka',
        ]);

        AttendanceRecord::query()->create([
            'attendance_session_id' => $openSession->id,
            'student_id' => $presentStudent->id,
            'method' => 'qr_code',
            'status' => 'hadir',
            'checked_in_at' => '2026-11-05 08:10:00',
        ]);

        // Sesi 2: Terjadwal tetapi tidak pernah dibuka oleh dosen
        $unopenedSession = AttendanceSession::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'praktikum',
            'delivery_mode' => 'onsite',
            'title' => 'Sesi Tidak Pernah Dibuka',
            'starts_at' => '2026-11-05 08:00:00',
            'ends_at' => '2026-11-05 10:00:00',
            'attendance_opens_at' => '2026-11-05 07:45:00',
            'attendance_closes_at' => '2026-11-05 10:15:00',
            'allowed_methods' => ['qr_code'],
            'status' => 'terjadwal',
        ]);

        // 1. Cek pada 20 jam setelah starts_at (belum 24 jam): tidak boleh ditutup terlalu awal
        Carbon::setTestNow(Carbon::parse('2026-11-06 04:00:00', 'Asia/Jakarta'));
        AttendanceSession::closeExpiredSessions();

        $this->assertSame('terbuka', $openSession->fresh()->status);
        $this->assertSame('terjadwal', $unopenedSession->fresh()->status);

        // 2. Cek tepat pada 24 jam 5 menit setelah starts_at: keduanya harus ditutup otomatis
        Carbon::setTestNow(Carbon::parse('2026-11-06 08:05:00', 'Asia/Jakarta'));
        Artisan::call('schedule:run');

        $openSession->refresh();
        $this->assertSame('ditutup', $openSession->status);
        $this->assertNull($openSession->closed_by);
        $this->assertNotNull($openSession->closed_at);

        // Catatan hadir tidak tertimpa, dan mahasiswa yang belum tercatat menjadi tidak_hadir
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $openSession->id,
            'student_id' => $presentStudent->id,
            'status' => 'hadir',
            'method' => 'qr_code',
        ]);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $openSession->id,
            'student_id' => $absentStudent->id,
            'status' => 'tidak_hadir',
        ]);

        // Sesi yang tidak pernah dibuka tidak tertinggal berstatus terjadwal
        $unopenedSession->refresh();
        $this->assertSame('ditutup', $unopenedSession->status);
        $this->assertNull($unopenedSession->closed_by);
        $this->assertNotNull($unopenedSession->closed_at);
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $unopenedSession->id,
            'student_id' => $presentStudent->id,
            'status' => 'tidak_hadir',
        ]);

        Carbon::setTestNow();
    }
}

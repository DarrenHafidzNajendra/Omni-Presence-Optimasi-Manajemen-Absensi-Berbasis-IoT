<?php

namespace Tests\Feature;

use App\Enums\UserRole;
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
}

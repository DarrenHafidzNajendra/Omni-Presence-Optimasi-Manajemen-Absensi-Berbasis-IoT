<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CourseSectionLecturer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_sections_with_explicit_empty_states(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()
            ->assertSeeText('Statistik pengguna')
            ->assertSeeText('Total pengguna')
            ->assertSeeText('Admin')
            ->assertSeeText('Dosen')
            ->assertSeeText('Mahasiswa')
            ->assertSeeText('Statistik kehadiran')
            ->assertSeeText('Belum ada data koneksi')
            ->assertSeeText('QR Code')
            ->assertSeeText('Face Recognition')
            ->assertSeeText('Aktivitas terbaru')
            ->assertSeeText('Belum ada aktivitas')
            ->assertSeeText('Data belum tersedia');
    }

    public function test_dashboard_user_statistics_reflect_database_counts_and_update_when_users_change(): void
    {
        User::factory()->count(2)->create(['role' => UserRole::Admin->value]);
        User::factory()->count(1)->create(['role' => UserRole::Dosen->value]);
        User::factory()->count(3)->create(['role' => UserRole::Mahasiswa->value]);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $initialResponse = $this->actingAs($admin)->get(route('dashboard'));

        $initialResponse->assertOk()
            ->assertSee('data-statistic="total">7', false)
            ->assertSee('data-statistic="admin">3', false)
            ->assertSee('data-statistic="dosen">1', false)
            ->assertSee('data-statistic="mahasiswa">3', false);

        User::factory()->count(2)->create(['role' => UserRole::Dosen->value]);

        $updatedResponse = $this->get(route('dashboard'));

        $updatedResponse->assertOk()
            ->assertSee('data-statistic="total">9', false)
            ->assertSee('data-statistic="admin">3', false)
            ->assertSee('data-statistic="dosen">3', false)
            ->assertSee('data-statistic="mahasiswa">3', false);
    }

    public function test_dashboard_hides_user_statistics_from_non_admin_roles(): void
    {
        $dosen = User::factory()->create(['role' => UserRole::Dosen->value]);

        $response = $this->actingAs($dosen)->get(route('dashboard'));

        $response->assertOk()
            ->assertDontSeeText('Statistik pengguna')
            ->assertSeeText('Statistik kehadiran')
            ->assertSeeText('Kelas yang diampu');
    }

    public function test_dashboard_falls_back_to_cumulative_attendance_statistics_when_historical_records_exist(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $student = User::factory()->create(['role' => UserRole::Mahasiswa->value]);

        $course = Course::query()->create([
            'code' => 'IF999',
            'name' => 'Pemrograman Lanjut',
        ]);

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
            'replacement_deadline' => '2027-01-29',
            'is_active' => true,
        ]);

        $section = CourseSection::query()->create([
            'course_id' => $course->id,
            'academic_period_id' => $period->id,
            'name' => 'Kelas Demo',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $session = AttendanceSession::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan 1',
            'starts_at' => '2026-09-08 08:00:00',
            'ends_at' => '2026-09-08 10:00:00',
            'attendance_opens_at' => '2026-09-08 07:45:00',
            'attendance_closes_at' => '2026-09-08 10:15:00',
            'allowed_methods' => ['manual'],
            'status' => 'ditutup',
        ]);

        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'hadir',
            'method' => 'manual',
            'checked_in_at' => '2026-09-08 08:05:00',
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('data-statistic="attendance-total">1', false)
            ->assertSee('data-statistic="attendance-hadir">1', false)
            ->assertDontSeeText('Data belum tersedia');
    }

    public function test_lecturer_dashboard_shows_buka_presensi_button_when_schedule_start_time_is_reached(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 08:15:00', 'Asia/Jakarta'));

        $dosen = User::factory()->create(['role' => UserRole::Dosen->value]);
        $course = Course::query()->create([
            'code' => 'IF101',
            'name' => 'IoT Sistem Absensi',
        ]);
        $section = CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'Kelas IoT A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
        CourseSectionLecturer::query()->create([
            'course_section_id' => $section->id,
            'user_id' => $dosen->id,
            'teaching_type' => 'teori',
        ]);
        ClassSchedule::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'day_of_week' => 1, // Senin
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'timezone' => 'Asia/Jakarta',
            'location' => 'Lab IoT',
            'is_active' => true,
        ]);

        $response = $this->actingAs($dosen)->get(route('dashboard'));

        $response->assertOk()
            ->assertSeeText('Kelas IoT A')
            ->assertSeeText('Buka Presensi');

        Carbon::setTestNow();
    }
}

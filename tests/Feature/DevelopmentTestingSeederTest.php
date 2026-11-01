<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CourseSectionLecturer;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\AttendanceRecapService;
use Database\Seeders\DevelopmentTestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DevelopmentTestingSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_executes_successfully_and_is_idempotent(): void
    {
        // 1. Eksekusi pertama
        $this->seed(DevelopmentTestingSeeder::class);

        // Catat jumlah seluruh baris data setelah eksekusi pertama
        $countsBefore = [
            'users' => User::count(),
            'academic_periods' => AcademicPeriod::count(),
            'courses' => Course::count(),
            'course_sections' => CourseSection::count(),
            'lecturers' => CourseSectionLecturer::count(),
            'schedules' => ClassSchedule::count(),
            'enrollments' => Enrollment::count(),
            'sessions' => AttendanceSession::count(),
            'records' => AttendanceRecord::count(),
        ];

        // Verifikasi Akun Pengguna
        $this->assertDatabaseHas('users', [
            'email' => 'admin@omnipresence.local',
            'role' => 'admin',
        ]);
        $this->assertDatabaseHas('users', [
            'nidn' => '0001018501',
            'role' => 'dosen',
        ]);
        $this->assertDatabaseHas('users', [
            'nidn' => '0002028802',
            'role' => 'dosen',
        ]);
        $this->assertDatabaseHas('users', [
            'nidn' => '0003039003',
            'role' => 'dosen',
        ]);
        $this->assertEquals(12, User::query()->where('role', 'mahasiswa')->count());

        // Verifikasi Periode Akademik
        $period = AcademicPeriod::query()->where('name', 'Semester Ganjil 2026/2027')->firstOrFail();
        $this->assertEquals('2026-09-28', $period->ats_replacement_starts_on->format('Y-m-d'));
        $this->assertEquals('2026-10-02', $period->ats_replacement_ends_on->format('Y-m-d'));

        // Verifikasi Kelas dan Penugasan
        $sectionA = CourseSection::query()->where('name', 'Kelas A')->whereHas('course', function ($q) {
            $q->where('code', 'IF101');
        })->firstOrFail();
        $this->assertEquals(1, $sectionA->lecturers()->count());
        $this->assertEquals('0001018501', $sectionA->lecturers()->first()->user->nidn);

        // Verifikasi Rekap Deterministik pada Kelas Web A
        $recapService = app(AttendanceRecapService::class);
        $recap = $recapService->getRecap($sectionA);

        // 10 Mahasiswa aktif (Mahasiswa 11 enrollment nonaktif, Mahasiswa 12 user is_active = false)
        $this->assertEquals(10, $recap['totalActiveStudents']);

        // 3 Sesi terlaksana (Sesi 1 ditutup, Sesi 2 ditutup, Sesi 4 pengganti ditutup; Sesi 3 dibatalkan dieksklusi)
        $this->assertEquals(3, $recap['totalQualifyingSessions']);

        // Mahasiswa 1 (3312501001): Hadir di sesi 1, sesi 2, sesi 4 => 3/3 (100.0%)
        $mhs1 = $recap['studentRecaps']->firstWhere('nim', '3312501001');
        $this->assertEquals(3, $mhs1['hadir']);
        $this->assertEquals(0, $mhs1['terlambat']);
        $this->assertEquals(100.0, $mhs1['percentage']);

        // Mahasiswa 2 (3312501002): Hadir sesi 1, Terlambat sesi 2, Hadir sesi 4 => (2 hadir + 1 terlambat) / 3 => 100.0%
        $mhs2 = $recap['studentRecaps']->firstWhere('nim', '3312501002');
        $this->assertEquals(2, $mhs2['hadir']);
        $this->assertEquals(1, $mhs2['terlambat']);
        $this->assertEquals(100.0, $mhs2['percentage']);

        // Mahasiswa 5 (3312501005): Hadir sesi 1, Tidak hadir sesi 2, Hadir sesi 4 => (2 hadir) / 3 => 66.7%
        $mhs5 = $recap['studentRecaps']->firstWhere('nim', '3312501005');
        $this->assertEquals(2, $mhs5['hadir']);
        $this->assertEquals(1, $mhs5['tidak_hadir']);
        $this->assertEquals(66.7, $mhs5['percentage']);

        // Mahasiswa 6 (3312501006): Hadir sesi 1, Belum dicatat sesi 2, Hadir sesi 4 => (2 hadir) / 3 => 66.7%, Belum dicatat = 1
        $mhs6 = $recap['studentRecaps']->firstWhere('nim', '3312501006');
        $this->assertEquals(2, $mhs6['hadir']);
        $this->assertEquals(1, $mhs6['belum_dicatat']);
        $this->assertEquals(66.7, $mhs6['percentage']);

        // 2. Eksekusi kedua untuk memastikan tidak ada duplikasi data (strict idempotency)
        $this->seed(DevelopmentTestingSeeder::class);

        $countsAfter = [
            'users' => User::count(),
            'academic_periods' => AcademicPeriod::count(),
            'courses' => Course::count(),
            'course_sections' => CourseSection::count(),
            'lecturers' => CourseSectionLecturer::count(),
            'schedules' => ClassSchedule::count(),
            'enrollments' => Enrollment::count(),
            'sessions' => AttendanceSession::count(),
            'records' => AttendanceRecord::count(),
        ];

        // Pastikan jumlah setiap tabel identik persis sebelum dan sesudah eksekusi kedua
        $this->assertSame($countsBefore, $countsAfter, 'Pemanggilan seeder kedua kali menyebabkan penambahan atau duplikasi data!');

        // Pastikan hasil rekap tetap konsisten 100%
        $this->assertEquals(10, $recapService->getRecap($sectionA)['totalActiveStudents']);
        $this->assertEquals(3, $recapService->getRecap($sectionA)['totalQualifyingSessions']);
    }
}

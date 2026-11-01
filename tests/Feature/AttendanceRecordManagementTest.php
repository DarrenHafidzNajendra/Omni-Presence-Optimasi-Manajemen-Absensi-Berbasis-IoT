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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecordManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_lecturer_can_record_attendance_with_valid_statuses(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $student = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $this->enrollStudent($section, $student);

        // Sesi hari ini agar belum melewati batas waktu
        $now = Carbon::now('Asia/Jakarta');
        $session = $this->createAttendanceSession($section, [
            'starts_at' => $now->copy()->format('Y-m-d 08:00:00'),
            'ends_at' => $now->copy()->format('Y-m-d 10:00:00'),
            'status' => 'terbuka',
        ]);

        foreach (['hadir', 'terlambat', 'izin', 'sakit'] as $status) {
            $response = $this->actingAs($lecturer)->post(
                route('course-sections.attendance-sessions.records.store', [$section, $session]),
                [
                    'student_id' => $student->id,
                    'status' => $status,
                    'notes' => "Catatan status {$status}",
                ]
            );

            $response->assertRedirect();
            $this->assertDatabaseHas('attendance_records', [
                'attendance_session_id' => $session->id,
                'student_id' => $student->id,
                'status' => $status,
                'recorded_by' => $lecturer->id,
            ]);
        }
    }

    public function test_unassigned_lecturer_and_student_cannot_record_attendance(): void
    {
        $section = $this->createCourseSection();
        $unassignedLecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $student = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $this->enrollStudent($section, $student);

        $session = $this->createAttendanceSession($section);

        $this->actingAs($unassignedLecturer)->post(
            route('course-sections.attendance-sessions.records.store', [$section, $session]),
            ['student_id' => $student->id, 'status' => 'hadir']
        )->assertForbidden();

        $this->actingAs($student)->post(
            route('course-sections.attendance-sessions.records.store', [$section, $session]),
            ['student_id' => $student->id, 'status' => 'hadir']
        )->assertForbidden();
    }

    public function test_cannot_record_attendance_for_unregistered_or_inactive_student(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $unregisteredStudent = User::factory()->create(['role' => UserRole::Mahasiswa->value]);

        $inactiveStudent = User::factory()->create(['role' => UserRole::Mahasiswa->value, 'is_active' => false]);
        $this->enrollStudent($section, $inactiveStudent, 'nonaktif');

        $session = $this->createAttendanceSession($section);

        // Mahasiswa tidak terdaftar
        $this->actingAs($lecturer)->post(
            route('course-sections.attendance-sessions.records.store', [$section, $session]),
            ['student_id' => $unregisteredStudent->id, 'status' => 'hadir']
        )->assertSessionHasErrors('student_id');

        // Mahasiswa status enrollment nonaktif
        $this->actingAs($lecturer)->post(
            route('course-sections.attendance-sessions.records.store', [$section, $session]),
            ['student_id' => $inactiveStudent->id, 'status' => 'hadir']
        )->assertSessionHasErrors('student_id');
    }

    public function test_rejects_invalid_status(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $student = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $this->enrollStudent($section, $student);

        $session = $this->createAttendanceSession($section);

        $response = $this->actingAs($lecturer)->post(
            route('course-sections.attendance-sessions.records.store', [$section, $session]),
            ['student_id' => $student->id, 'status' => 'alpha_fiktif']
        );

        $response->assertSessionHasErrors('status');
    }

    public function test_lecturer_cannot_modify_attendance_after_session_calendar_date(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $student = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $this->enrollStudent($section, $student);

        // Sesi kemarin (sudah melewati akhir tanggal kalender lokal sesi)
        $yesterday = Carbon::now('Asia/Jakarta')->subDays(2);
        $session = $this->createAttendanceSession($section, [
            'starts_at' => $yesterday->format('Y-m-d 08:00:00'),
            'ends_at' => $yesterday->format('Y-m-d 10:00:00'),
            'status' => 'ditutup',
        ]);

        $response = $this->actingAs($lecturer)->post(
            route('course-sections.attendance-sessions.records.store', [$section, $session]),
            ['student_id' => $student->id, 'status' => 'hadir']
        );

        $response->assertSessionHasErrors('attendance');
        $this->assertDatabaseMissing('attendance_records', [
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_admin_can_correct_attendance_after_session_date_with_mandatory_reason(): void
    {
        $section = $this->createCourseSection();
        $admin = User::factory()->create(['role' => UserRole::Admin->value, 'name' => 'Bapak Admin']);
        $student = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $this->enrollStudent($section, $student);

        $yesterday = Carbon::now('Asia/Jakarta')->subDays(2);
        $session = $this->createAttendanceSession($section, [
            'starts_at' => $yesterday->format('Y-m-d 08:00:00'),
            'ends_at' => $yesterday->format('Y-m-d 10:00:00'),
            'status' => 'ditutup',
        ]);

        // Catatan awal adalah 'tidak_hadir'
        $record = AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'tidak_hadir',
            'method' => 'manual',
            'notes' => 'Catatan awal',
        ]);

        // Coba koreksi tanpa alasan -> harus ditolak
        $responseWithoutNotes = $this->actingAs($admin)->post(
            route('course-sections.attendance-sessions.records.store', [$section, $session]),
            [
                'student_id' => $student->id,
                'status' => 'hadir',
                'notes' => '',
            ]
        );
        $responseWithoutNotes->assertSessionHasErrors('notes');

        // Koreksi dengan alasan yang jelas
        $responseWithNotes = $this->actingAs($admin)->post(
            route('course-sections.attendance-sessions.records.store', [$section, $session]),
            [
                'student_id' => $student->id,
                'status' => 'hadir',
                'notes' => 'Mahasiswa ada dispensasi lomba dari dekanat',
            ]
        );

        $responseWithNotes->assertRedirect();

        $record->refresh();
        $this->assertSame('hadir', $record->status);
        $this->assertSame($admin->id, $record->recorded_by);
        $this->assertStringContainsString('Catatan awal', $record->notes);
        $this->assertStringContainsString('Mahasiswa ada dispensasi lomba dari dekanat', $record->notes);
        $this->assertStringContainsString('Koreksi Admin oleh Bapak Admin', $record->notes);
    }

    public function test_session_closure_creates_tidak_hadir_without_overwriting_existing_records(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $student1 = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $student2 = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $student3 = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $this->enrollStudent($section, $student1);
        $this->enrollStudent($section, $student2);
        $this->enrollStudent($section, $student3);

        $session = $this->createAttendanceSession($section, ['status' => 'terbuka']);

        // Student 1 hadir
        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $student1->id,
            'status' => 'hadir',
            'method' => 'manual',
            'notes' => 'Hadir tepat waktu',
        ]);

        // Student 2 izin
        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $student2->id,
            'status' => 'izin',
            'method' => 'manual',
            'notes' => 'Surat izin terlampir',
        ]);

        // Student 3 belum ada record

        // Tutup sesi
        $session->close($lecturer);

        // Student 1 tetap hadir
        $rec1 = AttendanceRecord::query()->where('attendance_session_id', $session->id)->where('student_id', $student1->id)->first();
        $this->assertSame('hadir', $rec1->status);
        $this->assertSame('Hadir tepat waktu', $rec1->notes);

        // Student 2 tetap izin
        $rec2 = AttendanceRecord::query()->where('attendance_session_id', $session->id)->where('student_id', $student2->id)->first();
        $this->assertSame('izin', $rec2->status);
        $this->assertSame('Surat izin terlampir', $rec2->notes);

        // Student 3 dibuatkan tidak_hadir otomatis
        $rec3 = AttendanceRecord::query()->where('attendance_session_id', $session->id)->where('student_id', $student3->id)->first();
        $this->assertNotNull($rec3);
        $this->assertSame('tidak_hadir', $rec3->status);
        $this->assertSame($lecturer->id, $rec3->recorded_by);
    }

    public function test_detail_view_calculates_and_displays_attendance_summary_correctly(): void
    {
        $section = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);
        $this->assignLecturer($section, $lecturer);

        $students = User::factory()->count(6)->create(['role' => UserRole::Mahasiswa->value]);
        foreach ($students as $student) {
            $this->enrollStudent($section, $student);
        }

        // Mahasiswa tidak aktif dalam kelas seharusnya tidak dihitung
        $inactiveStudent = User::factory()->create(['role' => UserRole::Mahasiswa->value]);
        $this->enrollStudent($section, $inactiveStudent, 'nonaktif');

        $session = $this->createAttendanceSession($section, ['status' => 'terbuka']);

        // Kasus 1: Belum ada mahasiswa dicatat (6 mahasiswa aktif, 0 recorded)
        $response = $this->actingAs($lecturer)->get(
            route('course-sections.attendance-sessions.show', [$section, $session])
        );

        $response->assertOk();
        $response->assertViewHas('summary', function ($summary): bool {
            return $summary['total'] === 6
                && $summary['hadir'] === 0
                && $summary['terlambat'] === 0
                && $summary['izin'] === 0
                && $summary['sakit'] === 0
                && $summary['tidak_hadir'] === 0
                && $summary['belum_dicatat'] === 6
                && $summary['recorded'] === 0
                && $summary['percentage'] === null;
        });
        // Pastikan tampilan menampilkan strip "—", bukan "0%"
        $response->assertSee('—');
        $response->assertSee('Ringkasan Kehadiran Sesi');

        // Kasus 2: Catat presensi
        // 1 hadir, 1 terlambat, 1 izin, 1 sakit, 1 tidak_hadir, 1 belum_dicatat (total 6 aktif)
        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $students[0]->id,
            'status' => 'hadir',
            'method' => 'manual',
        ]);
        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $students[1]->id,
            'status' => 'terlambat',
            'method' => 'manual',
        ]);
        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $students[2]->id,
            'status' => 'izin',
            'method' => 'manual',
        ]);
        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $students[3]->id,
            'status' => 'sakit',
            'method' => 'manual',
        ]);
        AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $students[4]->id,
            'status' => 'tidak_hadir',
            'method' => 'manual',
        ]);
        // $students[5] belum dicatat

        $response = $this->actingAs($lecturer)->get(
            route('course-sections.attendance-sessions.show', [$section, $session])
        );

        $response->assertOk();
        // Dari 5 yang tercatat: 2 hadir (1 hadir + 1 terlambat). Persentase: 2 / 5 * 100 = 40.0%
        // Mahasiswa ke-6 belum dicatat dan tidak dimasukkan ke denominator persentase
        $response->assertViewHas('summary', function ($summary): bool {
            return $summary['total'] === 6
                && $summary['hadir'] === 1
                && $summary['terlambat'] === 1
                && $summary['izin'] === 1
                && $summary['sakit'] === 1
                && $summary['tidak_hadir'] === 1
                && $summary['belum_dicatat'] === 1
                && $summary['recorded'] === 5
                && $summary['present'] === 2
                && $summary['percentage'] === 40.0;
        });

        $response->assertSee('40%');
    }

    private function createCourseSection(string $name = 'Kelas A'): CourseSection
    {
        $course = Course::query()->create([
            'code' => 'CS'.fake()->unique()->numerify('###'),
            'name' => 'Mata Kuliah '.fake()->unique()->numerify('###'),
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
            'replacement_deadline' => '2027-01-30',
            'is_active' => true,
        ]);

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

    private function enrollStudent(CourseSection $section, User $student, string $status = 'aktif'): Enrollment
    {
        return Enrollment::query()->create([
            'course_section_id' => $section->id,
            'student_id' => $student->id,
            'status' => $status,
        ]);
    }
}

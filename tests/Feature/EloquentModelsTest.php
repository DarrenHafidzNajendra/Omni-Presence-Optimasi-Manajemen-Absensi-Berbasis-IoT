<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CourseSectionLecturer;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EloquentModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_resolve_their_primary_relationships(): void
    {
        $course = Course::query()->create([
            'code' => 'CS101',
            'name' => 'Pemrograman Dasar',
            'description' => 'Dasar pemrograman',
            'is_active' => true,
        ]);
        $section = CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
        $lecturer = User::factory()->create([
            'email' => 'lecturer@example.test',
            'role' => 'dosen',
        ]);
        $student = User::factory()->create(['email' => 'student@example.test']);
        $assignment = CourseSectionLecturer::query()->create([
            'course_section_id' => $section->id,
            'user_id' => $lecturer->id,
            'teaching_type' => 'teori',
        ]);
        $enrollment = Enrollment::query()->create([
            'course_section_id' => $section->id,
            'student_id' => $student->id,
            'status' => 'aktif',
        ]);
        $schedule = ClassSchedule::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'timezone' => 'Asia/Jakarta',
            'location' => 'Ruang A',
            'meeting_url' => null,
            'valid_from' => '2026-10-01',
            'valid_until' => '2027-01-31',
            'is_active' => true,
        ]);
        $session = AttendanceSession::query()->create([
            'class_schedule_id' => $schedule->id,
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan 1',
            'starts_at' => '2026-10-26 08:00:00',
            'ends_at' => '2026-10-26 10:00:00',
            'attendance_opens_at' => '2026-10-26 07:45:00',
            'attendance_closes_at' => '2026-10-26 10:15:00',
            'allowed_methods' => ['qr_code', 'face_recognition'],
            'status' => 'terbuka',
        ]);
        $record = AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'method' => 'qr_code',
            'status' => 'hadir',
            'checked_in_at' => '2026-10-26 08:05:00',
            'notes' => null,
        ]);

        $this->assertSame($course->id, $section->course->id);
        $this->assertSame($section->id, $course->sections->first()->id);
        $this->assertSame($assignment->id, $section->lecturers->first()->id);
        $this->assertSame($enrollment->id, $section->enrollments->first()->id);
        $this->assertSame($schedule->id, $section->schedules->first()->id);
        $this->assertSame($session->id, $section->attendanceSessions->first()->id);
        $this->assertSame($section->id, $assignment->courseSection->id);
        $this->assertSame($lecturer->id, $assignment->user->id);
        $this->assertSame($assignment->id, $lecturer->courseSectionLecturers->first()->id);
        $this->assertSame($section->id, $lecturer->courseSectionLecturers->first()->courseSection->id);
        $this->assertSame($section->id, $enrollment->courseSection->id);
        $this->assertSame($student->id, $enrollment->student->id);
        $this->assertSame($section->id, $schedule->courseSection->id);
        $this->assertSame($session->id, $schedule->attendanceSessions->first()->id);
        $this->assertSame($schedule->id, $session->classSchedule->id);
        $this->assertSame($section->id, $session->courseSection->id);
        $this->assertSame($record->id, $session->attendanceRecords->first()->id);
        $this->assertSame($session->id, $record->attendanceSession->id);
        $this->assertSame($student->id, $record->student->id);
    }

    public function test_models_cast_schema_attributes_to_expected_types(): void
    {
        $course = Course::query()->create([
            'code' => 'CS102',
            'name' => 'Struktur Data',
            'is_active' => 1,
        ]);
        $section = CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'B',
            'academic_year' => '2026/2027',
            'semester' => 'genap',
            'is_active' => 1,
        ]);
        $schedule = ClassSchedule::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'praktikum',
            'delivery_mode' => 'online',
            'day_of_week' => '2',
            'starts_at' => '13:00',
            'ends_at' => '15:00',
            'timezone' => 'Asia/Jakarta',
            'valid_from' => '2026-11-01',
            'valid_until' => '2027-02-01',
            'is_active' => 1,
        ]);
        $session = AttendanceSession::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'praktikum',
            'delivery_mode' => 'online',
            'title' => 'Praktikum 1',
            'starts_at' => '2026-11-02 13:00:00',
            'ends_at' => '2026-11-02 15:00:00',
            'attendance_opens_at' => '2026-11-02 12:45:00',
            'attendance_closes_at' => '2026-11-02 15:15:00',
            'allowed_methods' => ['qr_code'],
            'status' => 'terjadwal',
        ]);
        $record = AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => User::factory()->create()->id,
            'method' => 'manual',
            'status' => 'izin',
            'checked_in_at' => '2026-11-02 13:10:00',
        ]);

        $this->assertIsBool($course->is_active);
        $this->assertIsBool($section->is_active);
        $this->assertIsBool($schedule->is_active);
        $this->assertIsInt($schedule->day_of_week);
        $this->assertInstanceOf(Carbon::class, $schedule->valid_from);
        $this->assertInstanceOf(Carbon::class, $schedule->valid_until);
        $this->assertInstanceOf(Carbon::class, $session->starts_at);
        $this->assertInstanceOf(Carbon::class, $session->ends_at);
        $this->assertInstanceOf(Carbon::class, $session->attendance_opens_at);
        $this->assertInstanceOf(Carbon::class, $session->attendance_closes_at);
        $this->assertIsArray($session->allowed_methods);
        $this->assertSame(['qr_code'], $session->allowed_methods);
        $this->assertInstanceOf(Carbon::class, $record->checked_in_at);
    }

    public function test_audit_foreign_keys_are_not_mass_assignable_but_relations_remain_available(): void
    {
        $course = Course::query()->create([
            'code' => 'CS103',
            'name' => 'Basis Data',
        ]);
        $section = CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'C',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);
        $user = User::factory()->create();
        $session = AttendanceSession::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan Audit',
            'starts_at' => '2026-12-01 08:00:00',
            'ends_at' => '2026-12-01 10:00:00',
            'attendance_opens_at' => '2026-12-01 07:45:00',
            'attendance_closes_at' => '2026-12-01 10:15:00',
            'allowed_methods' => ['qr_code'],
            'status' => 'terjadwal',
            'opened_by' => $user->id,
        ]);
        $record = AttendanceRecord::query()->create([
            'attendance_session_id' => $session->id,
            'student_id' => $user->id,
            'method' => 'manual',
            'status' => 'hadir',
            'recorded_by' => $user->id,
        ]);

        $this->assertNull($session->opened_by);
        $this->assertNull($record->recorded_by);

        $session->opened_by = $user->id;
        $session->save();
        $record->recorded_by = $user->id;
        $record->save();

        $this->assertSame($user->id, $session->openedBy()->first()->id);
        $this->assertSame($user->id, $record->recordedBy()->first()->id);
    }
}

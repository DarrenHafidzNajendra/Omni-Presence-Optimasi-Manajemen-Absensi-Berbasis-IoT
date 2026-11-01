<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AttendanceSession;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CourseSectionLecturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AttendanceSessionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_attendance_session_for_any_course_section(): void
    {
        $section = $this->createCourseSection();
        $session = $this->createAttendanceSession($section);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $this->assertTrue(Gate::forUser($admin)->allows('manage', $session));
        $this->assertTrue(Gate::forUser($admin)->allows('manageAttendance', $session));
        $this->assertTrue(Gate::forUser($admin)->allows('open', $session));
        $this->assertTrue(Gate::forUser($admin)->allows('close', $session));
        $this->assertTrue(Gate::forUser($admin)->allows('cancel', $session));
        $this->assertTrue(Gate::forUser($admin)->allows('create', [AttendanceSession::class, $section]));
    }

    public function test_assigned_active_lecturer_can_manage_attendance_session_for_their_course_section(): void
    {
        $section = $this->createCourseSection();
        $session = $this->createAttendanceSession($section);
        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);

        CourseSectionLecturer::query()->create([
            'course_section_id' => $section->id,
            'user_id' => $lecturer->id,
            'teaching_type' => 'teori',
        ]);

        $this->assertTrue(Gate::forUser($lecturer)->allows('manage', $session));
        $this->assertTrue(Gate::forUser($lecturer)->allows('manageAttendance', $session));
        $this->assertTrue(Gate::forUser($lecturer)->allows('open', $session));
        $this->assertTrue(Gate::forUser($lecturer)->allows('close', $session));
        $this->assertTrue(Gate::forUser($lecturer)->allows('cancel', $session));
        $this->assertTrue(Gate::forUser($lecturer)->allows('create', [AttendanceSession::class, $section]));
    }

    public function test_unassigned_lecturer_cannot_manage_attendance_session(): void
    {
        $section = $this->createCourseSection();
        $session = $this->createAttendanceSession($section);
        $unassignedLecturer = User::factory()->create(['role' => UserRole::Dosen->value]);

        $this->assertFalse(Gate::forUser($unassignedLecturer)->allows('manage', $session));
        $this->assertFalse(Gate::forUser($unassignedLecturer)->allows('manageAttendance', $session));
        $this->assertFalse(Gate::forUser($unassignedLecturer)->allows('open', $session));
        $this->assertFalse(Gate::forUser($unassignedLecturer)->allows('close', $session));
        $this->assertFalse(Gate::forUser($unassignedLecturer)->allows('cancel', $session));
        $this->assertFalse(Gate::forUser($unassignedLecturer)->allows('create', [AttendanceSession::class, $section]));
    }

    public function test_lecturer_assigned_to_another_course_section_cannot_manage_attendance_session(): void
    {
        $assignedSection = $this->createCourseSection('Kelas Ditugaskan');
        $otherSection = $this->createCourseSection('Kelas Lain');
        $sessionOfOtherSection = $this->createAttendanceSession($otherSection);

        $lecturer = User::factory()->create(['role' => UserRole::Dosen->value]);

        CourseSectionLecturer::query()->create([
            'course_section_id' => $assignedSection->id,
            'user_id' => $lecturer->id,
            'teaching_type' => 'teori',
        ]);

        $this->assertFalse(Gate::forUser($lecturer)->allows('manage', $sessionOfOtherSection));
        $this->assertFalse(Gate::forUser($lecturer)->allows('manageAttendance', $sessionOfOtherSection));
        $this->assertFalse(Gate::forUser($lecturer)->allows('open', $sessionOfOtherSection));
        $this->assertFalse(Gate::forUser($lecturer)->allows('close', $sessionOfOtherSection));
        $this->assertFalse(Gate::forUser($lecturer)->allows('cancel', $sessionOfOtherSection));
        $this->assertFalse(Gate::forUser($lecturer)->allows('create', [AttendanceSession::class, $otherSection]));
    }

    public function test_inactive_lecturer_cannot_manage_attendance_session_even_with_an_assignment(): void
    {
        $section = $this->createCourseSection();
        $session = $this->createAttendanceSession($section);
        $inactiveLecturer = User::factory()->create([
            'role' => UserRole::Dosen->value,
            'is_active' => false,
        ]);

        CourseSectionLecturer::query()->create([
            'course_section_id' => $section->id,
            'user_id' => $inactiveLecturer->id,
            'teaching_type' => 'teori',
        ]);

        $this->assertFalse(Gate::forUser($inactiveLecturer)->allows('manage', $session));
        $this->assertFalse(Gate::forUser($inactiveLecturer)->allows('manageAttendance', $session));
        $this->assertFalse(Gate::forUser($inactiveLecturer)->allows('open', $session));
        $this->assertFalse(Gate::forUser($inactiveLecturer)->allows('close', $session));
        $this->assertFalse(Gate::forUser($inactiveLecturer)->allows('cancel', $session));
        $this->assertFalse(Gate::forUser($inactiveLecturer)->allows('create', [AttendanceSession::class, $section]));
    }

    public function test_student_cannot_manage_attendance_session(): void
    {
        $section = $this->createCourseSection();
        $session = $this->createAttendanceSession($section);
        $student = User::factory()->create(['role' => UserRole::Mahasiswa->value]);

        $this->assertFalse(Gate::forUser($student)->allows('manage', $session));
        $this->assertFalse(Gate::forUser($student)->allows('manageAttendance', $session));
        $this->assertFalse(Gate::forUser($student)->allows('open', $session));
        $this->assertFalse(Gate::forUser($student)->allows('close', $session));
        $this->assertFalse(Gate::forUser($student)->allows('cancel', $session));
        $this->assertFalse(Gate::forUser($student)->allows('create', [AttendanceSession::class, $section]));
    }

    public function test_guest_cannot_manage_attendance_session(): void
    {
        $section = $this->createCourseSection();
        $session = $this->createAttendanceSession($section);

        $this->assertFalse(Gate::allows('manage', $session));
        $this->assertFalse(Gate::allows('manageAttendance', $session));
        $this->assertFalse(Gate::allows('open', $session));
        $this->assertFalse(Gate::allows('close', $session));
        $this->assertFalse(Gate::allows('cancel', $session));
        $this->assertFalse(Gate::allows('create', [AttendanceSession::class, $section]));
    }

    public function test_mismatched_course_section_and_attendance_session_is_rejected(): void
    {
        $sectionA = $this->createCourseSection('Kelas A');
        $sectionB = $this->createCourseSection('Kelas B');
        $sessionOfA = $this->createAttendanceSession($sectionA);

        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $lecturerA = User::factory()->create(['role' => UserRole::Dosen->value]);

        CourseSectionLecturer::query()->create([
            'course_section_id' => $sectionA->id,
            'user_id' => $lecturerA->id,
            'teaching_type' => 'teori',
        ]);

        // When a mismatched CourseSection ($sectionB) is passed alongside sessionOfA, it must be rejected
        $this->assertFalse(Gate::forUser($admin)->allows('manage', [$sessionOfA, $sectionB]));
        $this->assertFalse(Gate::forUser($lecturerA)->allows('manage', [$sessionOfA, $sectionB]));
        $this->assertFalse(Gate::forUser($admin)->allows('open', [$sessionOfA, $sectionB]));
        $this->assertFalse(Gate::forUser($lecturerA)->allows('open', [$sessionOfA, $sectionB]));

        // Passing the matching CourseSection ($sectionA) must succeed
        $this->assertTrue(Gate::forUser($admin)->allows('manage', [$sessionOfA, $sectionA]));
        $this->assertTrue(Gate::forUser($lecturerA)->allows('manage', [$sessionOfA, $sectionA]));
        $this->assertTrue(Gate::forUser($admin)->allows('open', [$sessionOfA, $sectionA]));
        $this->assertTrue(Gate::forUser($lecturerA)->allows('open', [$sessionOfA, $sectionA]));
    }

    private function createCourseSection(string $name = 'Kelas A'): CourseSection
    {
        $course = Course::query()->create([
            'code' => 'CS'.fake()->unique()->numerify('###'),
            'name' => 'Mata Kuliah '.fake()->unique()->numerify('###'),
        ]);

        return CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => $name,
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
    }

    private function createAttendanceSession(CourseSection $section): AttendanceSession
    {
        return AttendanceSession::query()->create([
            'course_section_id' => $section->id,
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'title' => 'Pertemuan 1',
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(3),
            'attendance_opens_at' => now()->addMinutes(45),
            'attendance_closes_at' => now()->addHours(3)->addMinutes(15),
            'allowed_methods' => ['qr_code'],
            'status' => 'terjadwal',
        ]);
    }
}

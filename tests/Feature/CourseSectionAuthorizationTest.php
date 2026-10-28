<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CourseSectionLecturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class CourseSectionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_active_lecturer_can_manage_attendance_for_the_course_section(): void
    {
        $courseSection = $this->createCourseSection();
        $lecturer = User::factory()->create(['role' => 'dosen']);
        CourseSectionLecturer::query()->create([
            'course_section_id' => $courseSection->id,
            'user_id' => $lecturer->id,
            'teaching_type' => 'teori',
        ]);

        $this->assertTrue(Gate::forUser($lecturer)->allows('manageAttendance', $courseSection));
    }

    public function test_unassigned_lecturer_cannot_manage_attendance_for_the_course_section(): void
    {
        $this->assertFalse(
            Gate::forUser(User::factory()->create(['role' => 'dosen']))
                ->allows('manageAttendance', $this->createCourseSection()),
        );
    }

    public function test_lecturer_assignment_to_another_course_section_does_not_grant_access(): void
    {
        $assignedSection = $this->createCourseSection('Kelas Ditugaskan');
        $otherSection = $this->createCourseSection('Kelas Lain');
        $lecturer = User::factory()->create(['role' => 'dosen']);
        CourseSectionLecturer::query()->create([
            'course_section_id' => $assignedSection->id,
            'user_id' => $lecturer->id,
            'teaching_type' => 'teori',
        ]);

        $this->assertTrue(Gate::forUser($lecturer)->allows('manageAttendance', $assignedSection));
        $this->assertFalse(Gate::forUser($lecturer)->allows('manageAttendance', $otherSection));
    }

    public function test_inactive_lecturer_cannot_manage_attendance_even_with_an_assignment(): void
    {
        $courseSection = $this->createCourseSection();
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'is_active' => false,
        ]);
        CourseSectionLecturer::query()->create([
            'course_section_id' => $courseSection->id,
            'user_id' => $lecturer->id,
            'teaching_type' => 'teori',
        ]);

        $this->assertFalse(Gate::forUser($lecturer)->allows('manageAttendance', $courseSection));
    }

    public function test_student_cannot_manage_attendance(): void
    {
        $this->assertFalse(
            Gate::forUser(User::factory()->create(['role' => 'mahasiswa']))
                ->allows('manageAttendance', $this->createCourseSection()),
        );
    }

    public function test_guest_cannot_manage_attendance(): void
    {
        $this->assertFalse(Gate::allows('manageAttendance', $this->createCourseSection()));
    }

    public function test_admin_can_manage_attendance_for_any_course_section(): void
    {
        $this->assertTrue(
            Gate::forUser(User::factory()->create(['role' => 'admin']))
                ->allows('manageAttendance', $this->createCourseSection()),
        );
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
        ]);
    }
}

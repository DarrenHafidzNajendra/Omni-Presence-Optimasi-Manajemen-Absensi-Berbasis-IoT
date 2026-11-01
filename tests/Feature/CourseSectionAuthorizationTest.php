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

    public function test_lecturer_sees_only_assigned_classes_and_sidebar_link(): void
    {
        $assignedSection1 = $this->createCourseSection('Kelas Teori');
        $assignedSection2 = $this->createCourseSection('Kelas Praktikum');
        $unassignedSection = $this->createCourseSection('Kelas Dosen Lain');

        $lecturer = User::factory()->create(['role' => 'dosen']);

        // Penugasan teori
        CourseSectionLecturer::query()->create([
            'course_section_id' => $assignedSection1->id,
            'user_id' => $lecturer->id,
            'teaching_type' => 'teori',
        ]);

        // Penugasan praktikum
        CourseSectionLecturer::query()->create([
            'course_section_id' => $assignedSection2->id,
            'user_id' => $lecturer->id,
            'teaching_type' => 'praktikum',
        ]);

        $response = $this->actingAs($lecturer)->get(route('course-sections.index'));

        $response->assertOk()
            ->assertSeeText('Kelas Saya')
            ->assertSeeText('Kelas Teori')
            ->assertSeeText('Kelas Praktikum')
            ->assertDontSeeText('Kelas Dosen Lain')
            ->assertSeeText('Kelola Absensi')
            ->assertSee(route('course-sections.attendance-sessions.index', $assignedSection1), false)
            ->assertSee(route('course-sections.attendance-sessions.index', $assignedSection2), false)
            // Tidak menampilkan tombol admin
            ->assertDontSeeText('Tambah Kelas')
            ->assertDontSeeText('Detail')
            ->assertDontSeeText('Edit');
    }

    public function test_lecturer_without_assignments_sees_empty_state_without_error(): void
    {
        $lecturer = User::factory()->create(['role' => 'dosen']);
        $this->createCourseSection('Kelas Tersedia');

        $response = $this->actingAs($lecturer)->get(route('course-sections.index'));

        $response->assertOk()
            ->assertSeeText('Anda belum ditugaskan ke kelas mana pun.')
            ->assertDontSeeText('Kelas Tersedia');
    }

    public function test_lecturer_cannot_access_attendance_sessions_of_unassigned_class(): void
    {
        $unassignedSection = $this->createCourseSection('Kelas Terlarang');
        $lecturer = User::factory()->create(['role' => 'dosen']);

        $response = $this->actingAs($lecturer)->get(
            route('course-sections.attendance-sessions.index', $unassignedSection)
        );

        $response->assertForbidden();
    }

    public function test_admin_sees_all_course_sections_on_index(): void
    {
        $section1 = $this->createCourseSection('Kelas 1');
        $section2 = $this->createCourseSection('Kelas 2');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('course-sections.index'));

        $response->assertOk()
            ->assertSeeText('Daftar Kelas')
            ->assertSeeText('Kelas 1')
            ->assertSeeText('Kelas 2')
            ->assertSeeText('Tambah Kelas')
            ->assertSeeText('Detail')
            ->assertSeeText('Edit');
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

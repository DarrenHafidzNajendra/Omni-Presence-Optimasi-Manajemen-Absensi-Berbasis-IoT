<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CourseSectionLecturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSectionLecturerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_or_create_course_section_lecturer_assignments(): void
    {
        $courseSection = $this->createCourseSection();

        $this->get(route('course-sections.show', $courseSection))
            ->assertRedirectToRoute('login');
        $this->post(route('course-sections.lecturers.store', $courseSection))
            ->assertRedirectToRoute('login');
    }

    public function test_only_admin_can_manage_course_section_lecturer_assignments(): void
    {
        $courseSection = $this->createCourseSection();

        foreach (['dosen', 'mahasiswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->post(route('course-sections.lecturers.store', $courseSection), [
                    'user_id' => $user->id,
                    'teaching_type' => 'teori',
                ])
                ->assertForbidden();
        }
    }

    public function test_admin_can_assign_an_active_lecturer_to_a_course_section(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'nidn' => '123456',
            'name' => 'Dosen Aktif',
        ]);

        $response = $this->actingAs($admin)->post(route('course-sections.lecturers.store', $courseSection), [
            'user_id' => $lecturer->id,
            'teaching_type' => 'teori',
        ]);

        $response->assertRedirectToRoute('course-sections.show', $courseSection)
            ->assertSessionHas('success', 'Dosen berhasil ditugaskan ke kelas.');
        $this->assertDatabaseHas('course_section_lecturers', [
            'course_section_id' => $courseSection->id,
            'user_id' => $lecturer->id,
            'teaching_type' => 'teori',
        ]);

        $this->actingAs($admin)->get(route('course-sections.show', $courseSection))
            ->assertSeeText('Dosen Aktif')
            ->assertSeeText('123456')
            ->assertSeeText('teori');
    }

    public function test_admin_and_student_cannot_be_assigned_as_lecturers(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        foreach ([$admin, $student] as $user) {
            $this->actingAs($admin)
                ->from(route('course-sections.show', $courseSection))
                ->post(route('course-sections.lecturers.store', $courseSection), [
                    'user_id' => $user->id,
                    'teaching_type' => 'teori',
                ])
                ->assertRedirect(route('course-sections.show', $courseSection))
                ->assertSessionHasErrors('user_id');
        }
    }

    public function test_inactive_lecturer_cannot_be_assigned_to_a_new_course_section(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->from(route('course-sections.show', $courseSection))
            ->post(route('course-sections.lecturers.store', $courseSection), [
                'user_id' => $lecturer->id,
                'teaching_type' => 'praktikum',
            ])
            ->assertRedirect(route('course-sections.show', $courseSection))
            ->assertSessionHasErrors('user_id');
    }

    public function test_only_supported_teaching_types_are_accepted(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);

        foreach (['teori', 'praktikum'] as $teachingType) {
            $this->actingAs($admin)
                ->post(route('course-sections.lecturers.store', $courseSection), [
                    'user_id' => $lecturer->id,
                    'teaching_type' => $teachingType,
                ])
                ->assertRedirectToRoute('course-sections.show', $courseSection);
        }

        $this->actingAs($admin)
            ->from(route('course-sections.show', $courseSection))
            ->post(route('course-sections.lecturers.store', $courseSection), [
                'user_id' => $lecturer->id,
                'teaching_type' => 'asisten',
            ])
            ->assertRedirect(route('course-sections.show', $courseSection))
            ->assertSessionHasErrors('teaching_type');
    }

    public function test_duplicate_lecturer_assignment_is_rejected(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create(['role' => 'dosen']);

        CourseSectionLecturer::query()->create([
            'course_section_id' => $courseSection->id,
            'user_id' => $lecturer->id,
            'teaching_type' => 'teori',
        ]);

        $this->actingAs($admin)
            ->from(route('course-sections.show', $courseSection))
            ->post(route('course-sections.lecturers.store', $courseSection), [
                'user_id' => $lecturer->id,
                'teaching_type' => 'teori',
            ])
            ->assertRedirect(route('course-sections.show', $courseSection))
            ->assertSessionHasErrors('teaching_type');
    }

    public function test_inactive_assigned_lecturer_remains_visible_on_course_section_details(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $lecturer = User::factory()->create([
            'role' => 'dosen',
            'is_active' => false,
            'nidn' => '654321',
            'name' => 'Dosen Nonaktif',
        ]);
        CourseSectionLecturer::query()->create([
            'course_section_id' => $courseSection->id,
            'user_id' => $lecturer->id,
            'teaching_type' => 'praktikum',
        ]);

        $this->actingAs($admin)->get(route('course-sections.show', $courseSection))
            ->assertOk()
            ->assertSeeText('Dosen Nonaktif')
            ->assertSeeText('654321')
            ->assertSeeText('Nonaktif')
            ->assertSeeText('praktikum')
            ->assertDontSee('value="'.$lecturer->id.'"', false);
    }

    public function test_missing_course_section_returns_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('course-sections.show', 999999))
            ->assertNotFound();

        $this->actingAs($admin)
            ->post(route('course-sections.lecturers.store', 999999), [
                'user_id' => 999999,
                'teaching_type' => 'teori',
            ])
            ->assertNotFound();
    }

    private function createCourseSection(): CourseSection
    {
        $course = Course::query()->create([
            'code' => 'CS'.fake()->unique()->numerify('###'),
            'name' => 'Mata Kuliah '.fake()->unique()->numerify('###'),
        ]);

        return CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);
    }
}

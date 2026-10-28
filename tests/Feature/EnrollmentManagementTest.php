<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_enrollment_management(): void
    {
        $courseSection = $this->createCourseSection();

        $this->get(route('course-sections.enrollments.index', $courseSection))
            ->assertRedirectToRoute('login');
        $this->post(route('course-sections.enrollments.store', $courseSection))
            ->assertRedirectToRoute('login');
    }

    public function test_only_admin_can_manage_enrollments(): void
    {
        $courseSection = $this->createCourseSection();

        foreach (['dosen', 'mahasiswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('course-sections.enrollments.index', $courseSection))
                ->assertForbidden();
            $this->actingAs($user)
                ->post(route('course-sections.enrollments.store', $courseSection))
                ->assertForbidden();
        }
    }

    public function test_admin_can_view_enrollments_and_empty_state(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('course-sections.enrollments.index', $courseSection))
            ->assertRedirectToRoute('course-sections.show', $courseSection);

        $this->actingAs($admin)
            ->get(route('course-sections.show', $courseSection))
            ->assertOk()
            ->assertSeeText('Belum ada mahasiswa terdaftar.');
    }

    public function test_admin_can_enroll_an_active_student(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'nim' => '2026000001',
            'name' => 'Mahasiswa Aktif',
        ]);

        $response = $this->actingAs($admin)->post(
            route('course-sections.enrollments.store', $courseSection),
            ['student_id' => $student->id],
        );

        $response->assertRedirectToRoute('course-sections.show', $courseSection)
            ->assertSessionHas('success', 'Mahasiswa berhasil didaftarkan ke kelas.');
        $this->assertDatabaseHas('enrollments', [
            'course_section_id' => $courseSection->id,
            'student_id' => $student->id,
            'status' => 'aktif',
        ]);
        $this->assertTrue($student->enrollments()->where('course_section_id', $courseSection->id)->exists());

        $this->actingAs($admin)->get(route('course-sections.show', $courseSection))
            ->assertSeeText('Mahasiswa Aktif')
            ->assertSeeText('2026000001');
    }

    public function test_admin_cannot_enroll_a_student_in_an_inactive_course_section(): void
    {
        $courseSection = $this->createCourseSection();
        $courseSection->update(['is_active' => false]);
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'mahasiswa']);

        $this->actingAs($admin)
            ->from(route('course-sections.show', $courseSection))
            ->post(route('course-sections.enrollments.store', $courseSection), [
                'student_id' => $student->id,
            ])
            ->assertRedirect(route('course-sections.show', $courseSection))
            ->assertSessionHasErrors([
                'student_id' => 'Mahasiswa tidak dapat didaftarkan ke kelas nonaktif.',
            ]);

        $this->assertDatabaseMissing('enrollments', [
            'course_section_id' => $courseSection->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_only_students_can_be_enrolled(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $dosen = User::factory()->create(['role' => 'dosen']);

        $this->actingAs($admin)
            ->from(route('course-sections.show', $courseSection))
            ->post(route('course-sections.enrollments.store', $courseSection), [
                'student_id' => $dosen->id,
            ])
            ->assertRedirect(route('course-sections.show', $courseSection))
            ->assertSessionHasErrors('student_id');
    }

    public function test_inactive_student_cannot_be_enrolled(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->from(route('course-sections.show', $courseSection))
            ->post(route('course-sections.enrollments.store', $courseSection), [
                'student_id' => $student->id,
            ])
            ->assertRedirect(route('course-sections.show', $courseSection))
            ->assertSessionHasErrors('student_id');
    }

    public function test_duplicate_enrollment_is_rejected(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $courseSection->enrollments()->create(['student_id' => $student->id, 'status' => 'aktif']);

        $this->actingAs($admin)
            ->from(route('course-sections.show', $courseSection))
            ->post(route('course-sections.enrollments.store', $courseSection), [
                'student_id' => $student->id,
            ])
            ->assertRedirect(route('course-sections.show', $courseSection))
            ->assertSessionHasErrors('student_id');
    }

    public function test_enrollment_status_is_validated(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $enrollment = $courseSection->enrollments()->create(['student_id' => $student->id]);

        $this->actingAs($admin)
            ->from(route('course-sections.show', $courseSection))
            ->patch(route('course-sections.enrollments.status', [$courseSection, $enrollment]), [
                'status' => 'pending',
            ])
            ->assertRedirect(route('course-sections.show', $courseSection))
            ->assertSessionHasErrors('status');
    }

    public function test_admin_can_deactivate_and_delete_an_enrollment(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $enrollment = $courseSection->enrollments()->create(['student_id' => $student->id]);

        $this->actingAs($admin)
            ->patch(route('course-sections.enrollments.status', [$courseSection, $enrollment]), [
                'status' => 'nonaktif',
            ])
            ->assertRedirectToRoute('course-sections.show', $courseSection);
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id, 'status' => 'nonaktif']);

        $this->actingAs($admin)
            ->delete(route('course-sections.enrollments.destroy', [$courseSection, $enrollment]))
            ->assertRedirectToRoute('course-sections.show', $courseSection);
        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
        $this->assertDatabaseHas('users', ['id' => $student->id]);
    }

    public function test_enrollment_from_another_course_section_returns_not_found(): void
    {
        $courseSection = $this->createCourseSection();
        $otherSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $enrollment = $otherSection->enrollments()->create(['student_id' => $student->id]);

        $this->actingAs($admin)
            ->patch(route('course-sections.enrollments.status', [$courseSection, $enrollment]), [
                'status' => 'nonaktif',
            ])
            ->assertNotFound();

        $this->actingAs($admin)
            ->delete(route('course-sections.enrollments.destroy', [$courseSection, $enrollment]))
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

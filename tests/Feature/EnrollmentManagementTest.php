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
        $this->post(route('student.course-sections.enroll', $courseSection))
            ->assertRedirectToRoute('login');
    }

    public function test_only_admin_can_access_admin_enrollment_management_routes(): void
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

    public function test_admin_can_view_enrollments_and_manual_enrollment_form_is_removed(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('course-sections.enrollments.index', $courseSection))
            ->assertRedirectToRoute('course-sections.show', $courseSection);

        $this->actingAs($admin)
            ->get(route('course-sections.show', $courseSection))
            ->assertOk()
            ->assertSeeText('Belum ada mahasiswa terdaftar.')
            ->assertDontSeeText('Daftarkan Mahasiswa')
            ->assertDontSee(route('course-sections.enrollments.store', $courseSection), false);
    }

    public function test_admin_manual_enrollment_endpoint_is_disabled_with_forbidden_response(): void
    {
        $courseSection = $this->createCourseSection();
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'nim' => '2026000001',
            'name' => 'Mahasiswa Aktif',
        ]);

        $this->actingAs($admin)->post(
            route('course-sections.enrollments.store', $courseSection),
            ['student_id' => $student->id],
        )->assertForbidden();

        $this->assertDatabaseMissing('enrollments', [
            'course_section_id' => $courseSection->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_active_student_can_self_enroll_into_active_course_section(): void
    {
        $courseSection = $this->createCourseSection();
        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'nim' => '2026000001',
            'name' => 'Mahasiswa Mandiri',
            'is_active' => true,
        ]);

        // Dashboard mahasiswa menampilkan opsi enrollment mandiri
        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Enrollment mandiri mata kuliah')
            ->assertSeeText($courseSection->name)
            ->assertSee(route('student.course-sections.enroll', $courseSection), false);

        $response = $this->actingAs($student)
            ->post(route('student.course-sections.enroll', $courseSection));

        $response->assertRedirectToRoute('dashboard')
            ->assertSessionHas('success', 'Anda berhasil mendaftar ke kelas '.$courseSection->name.'.');

        $this->assertDatabaseHas('enrollments', [
            'course_section_id' => $courseSection->id,
            'student_id' => $student->id,
            'status' => 'aktif',
        ]);

        // Admin tetap dapat melihat mahasiswa yang sudah terdaftar pada detail kelas
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('course-sections.show', $courseSection))
            ->assertOk()
            ->assertSeeText('Mahasiswa Mandiri')
            ->assertSeeText('2026000001');
    }

    public function test_student_cannot_self_enroll_into_inactive_course_section_or_duplicate(): void
    {
        $inactiveSection = $this->createCourseSection();
        $inactiveSection->update(['is_active' => false]);

        $activeSection = $this->createCourseSection();

        $student = User::factory()->create([
            'role' => 'mahasiswa',
            'nim' => '2026000002',
            'is_active' => true,
        ]);

        // 1. Kelas nonaktif ditolak
        $this->actingAs($student)
            ->from(route('dashboard'))
            ->post(route('student.course-sections.enroll', $inactiveSection))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('enrollment');

        $this->assertDatabaseMissing('enrollments', [
            'course_section_id' => $inactiveSection->id,
            'student_id' => $student->id,
        ]);

        // 2. Enrollment pertama ke kelas aktif berhasil
        $this->actingAs($student)
            ->post(route('student.course-sections.enroll', $activeSection))
            ->assertRedirectToRoute('dashboard');

        // 3. Enrollment kedua (duplikat) ke kelas yang sama ditolak
        $this->actingAs($student)
            ->from(route('dashboard'))
            ->post(route('student.course-sections.enroll', $activeSection))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('enrollment');

        $this->assertSame(1, $activeSection->enrollments()->where('student_id', $student->id)->count());
    }

    public function test_admin_and_lecturer_cannot_use_student_self_enrollment_route(): void
    {
        $courseSection = $this->createCourseSection();

        foreach (['admin', 'dosen'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->post(route('student.course-sections.enroll', $courseSection))
                ->assertForbidden();
        }
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

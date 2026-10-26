<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSectionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_course_section_pages(): void
    {
        $this->get(route('course-sections.index'))->assertRedirectToRoute('login');
        $this->get(route('course-sections.create'))->assertRedirectToRoute('login');
        $this->post(route('course-sections.store'))->assertRedirectToRoute('login');

        $courseSection = CourseSection::query()->create([
            'course_id' => Course::query()->create(['code' => 'CS400', 'name' => 'Kursus Guest'])->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $this->get(route('course-sections.show', $courseSection))->assertRedirectToRoute('login');
        $this->get(route('course-sections.edit', $courseSection))->assertRedirectToRoute('login');
        $this->put(route('course-sections.update', $courseSection))->assertRedirectToRoute('login');
    }

    public function test_admin_can_view_course_section_list_and_create_form(): void
    {
        $course = Course::query()->create(['code' => 'CS401', 'name' => 'Analisis Data']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('course-sections.index'))
            ->assertOk()
            ->assertSeeText('Daftar Kelas')
            ->assertSeeText('Tambah Kelas');

        $this->actingAs($admin)->get(route('course-sections.create'))
            ->assertOk()
            ->assertSeeText('Tambah Kelas')
            ->assertSeeText('CS401 - Analisis Data')
            ->assertSee('name="course_id"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="academic_year"', false)
            ->assertSee('name="semester"', false);
    }

    public function test_admin_can_view_course_section_details_and_edit_form(): void
    {
        $course = Course::query()->create(['code' => 'CS408', 'name' => 'Sistem Informasi']);
        $courseSection = CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'Kelas B',
            'academic_year' => '2026/2027',
            'semester' => 'genap',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('course-sections.show', $courseSection))
            ->assertOk()
            ->assertSeeText('Detail Kelas')
            ->assertSeeText('CS408')
            ->assertSeeText('Sistem Informasi')
            ->assertSeeText('Kelas B')
            ->assertSeeText('2026/2027')
            ->assertSeeText('Genap')
            ->assertSeeText('Aktif')
            ->assertSee('href="'.route('course-sections.edit', $courseSection).'"', false);

        $this->actingAs($admin)->get(route('course-sections.edit', $courseSection))
            ->assertOk()
            ->assertSeeText('Edit Kelas')
            ->assertSee('name="course_id"', false)
            ->assertSee('CS408 - Sistem Informasi')
            ->assertSee('name="name"', false)
            ->assertSee('value="Kelas B"', false)
            ->assertSee('name="academic_year"', false)
            ->assertSee('value="2026/2027"', false)
            ->assertSee('name="semester"', false)
            ->assertSee('name="_method"', false)
            ->assertSee('value="PUT"', false);
    }

    public function test_edit_form_keeps_the_current_inactive_course_available(): void
    {
        $course = Course::query()->create([
            'code' => 'CS415',
            'name' => 'Kursus Nonaktif',
            'is_active' => false,
        ]);
        $courseSection = CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'Kelas D',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('course-sections.edit', $courseSection))
            ->assertOk()
            ->assertSeeText('CS415 - Kursus Nonaktif')
            ->assertSee('value="'.$course->id.'" selected', false);
    }

    public function test_non_admin_users_cannot_access_course_section_pages(): void
    {
        foreach (['dosen', 'mahasiswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('course-sections.index'))->assertForbidden();
            $this->actingAs($user)->get(route('course-sections.create'))->assertForbidden();
            $this->actingAs($user)->post(route('course-sections.store'))->assertForbidden();

            $courseSection = CourseSection::query()->create([
                'course_id' => Course::query()->create([
                    'code' => "CS{$role}01",
                    'name' => "Kursus {$role}",
                ])->id,
                'name' => 'Kelas A',
                'academic_year' => '2026/2027',
                'semester' => 'ganjil',
            ]);

            $this->actingAs($user)->get(route('course-sections.show', $courseSection))->assertForbidden();
            $this->actingAs($user)->get(route('course-sections.edit', $courseSection))->assertForbidden();
            $this->actingAs($user)->put(route('course-sections.update', $courseSection))->assertForbidden();
        }
    }

    public function test_admin_can_create_a_course_section(): void
    {
        $course = Course::query()->create(['code' => 'CS402', 'name' => 'Pemrograman Lanjut']);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('course-sections.store'), [
            'course_id' => $course->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $response->assertRedirectToRoute('course-sections.index')
            ->assertSessionHas('success', 'Kelas berhasil ditambahkan.');
        $this->assertDatabaseHas('course_sections', [
            'course_id' => $course->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_all_course_section_fields(): void
    {
        $oldCourse = Course::query()->create(['code' => 'CS409', 'name' => 'Kursus Lama']);
        $newCourse = Course::query()->create(['code' => 'CS410', 'name' => 'Kursus Baru']);
        $courseSection = CourseSection::query()->create([
            'course_id' => $oldCourse->id,
            'name' => 'Kelas Lama',
            'academic_year' => '2025/2026',
            'semester' => 'ganjil',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('course-sections.update', $courseSection), [
            'course_id' => $newCourse->id,
            'name' => 'Kelas Baru',
            'academic_year' => '2026/2027',
            'semester' => 'genap',
        ]);

        $response->assertRedirectToRoute('course-sections.show', $courseSection)
            ->assertSessionHas('success', 'Kelas berhasil diperbarui.');
        $this->assertDatabaseHas('course_sections', [
            'id' => $courseSection->id,
            'course_id' => $newCourse->id,
            'name' => 'Kelas Baru',
            'academic_year' => '2026/2027',
            'semester' => 'genap',
        ]);
    }

    public function test_course_section_can_keep_its_existing_unique_combination(): void
    {
        $course = Course::query()->create(['code' => 'CS411', 'name' => 'Kompilasi']);
        $courseSection = CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('course-sections.update', $courseSection), [
            'course_id' => $course->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $response->assertRedirectToRoute('course-sections.show', $courseSection)
            ->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('course_sections', ['id' => $courseSection->id, 'name' => 'Kelas A']);
    }

    public function test_duplicate_course_section_combination_from_another_record_is_rejected_on_update(): void
    {
        $course = Course::query()->create(['code' => 'CS412', 'name' => 'Keamanan Jaringan']);
        CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'genap',
        ]);
        $courseSection = CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'Kelas B',
            'academic_year' => '2026/2027',
            'semester' => 'genap',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('course-sections.edit', $courseSection))
            ->put(route('course-sections.update', $courseSection), [
                'course_id' => $course->id,
                'name' => 'Kelas A',
                'academic_year' => '2026/2027',
                'semester' => 'genap',
            ]);

        $response->assertRedirectToRoute('course-sections.edit', $courseSection)
            ->assertSessionHasErrors('name')
            ->assertSessionHas('_old_input.name', 'Kelas A');
        $this->assertDatabaseHas('course_sections', [
            'id' => $courseSection->id,
            'name' => 'Kelas B',
        ]);
    }

    public function test_course_section_update_validates_all_fields(): void
    {
        $course = Course::query()->create(['code' => 'CS413', 'name' => 'Teknik Digital']);
        $courseSection = CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'Kelas C',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('course-sections.edit', $courseSection))
            ->put(route('course-sections.update', $courseSection), [
                'course_id' => $course->id,
                'name' => '',
                'academic_year' => '',
                'semester' => 'salah',
            ]);

        $response->assertRedirectToRoute('course-sections.edit', $courseSection)
            ->assertSessionHasErrors(['name', 'academic_year', 'semester']);
        $this->assertDatabaseHas('course_sections', [
            'id' => $courseSection->id,
            'name' => 'Kelas C',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);
    }

    public function test_missing_course_section_returns_not_found_for_detail_edit_and_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('course-sections.show', 999999))->assertNotFound();
        $this->actingAs($admin)->get(route('course-sections.edit', 999999))->assertNotFound();
        $this->actingAs($admin)->put(route('course-sections.update', 999999), [
            'course_id' => 1,
            'name' => 'Tidak Ada',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ])->assertNotFound();
    }

    public function test_admin_can_deactivate_and_reactivate_a_course_section_without_deleting_it(): void
    {
        $course = Course::query()->create(['code' => 'CS416', 'name' => 'Administrasi Sistem']);
        $courseSection = CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $deactivation = $this->actingAs($admin)->patch(route('course-sections.status', $courseSection), [
            'is_active' => '0',
        ]);

        $deactivation->assertRedirectToRoute('course-sections.show', $courseSection)
            ->assertSessionHas('success', 'Kelas berhasil dinonaktifkan.');
        $this->assertDatabaseHas('course_sections', [
            'id' => $courseSection->id,
            'course_id' => $course->id,
            'name' => 'Kelas A',
            'is_active' => false,
        ]);

        $activation = $this->patch(route('course-sections.status', $courseSection), [
            'is_active' => '1',
        ]);

        $activation->assertRedirectToRoute('course-sections.show', $courseSection)
            ->assertSessionHas('success', 'Kelas berhasil diaktifkan.');
        $this->assertDatabaseHas('course_sections', [
            'id' => $courseSection->id,
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_from_changing_course_section_status(): void
    {
        $courseSection = CourseSection::query()->create([
            'course_id' => Course::query()->create(['code' => 'CS417', 'name' => 'Kursus Guest'])->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $this->patch(route('course-sections.status', $courseSection), ['is_active' => '0'])
            ->assertRedirectToRoute('login');
    }

    public function test_non_admin_users_cannot_change_course_section_status(): void
    {
        $courseSection = CourseSection::query()->create([
            'course_id' => Course::query()->create(['code' => 'CS418', 'name' => 'Kursus Role'])->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        foreach (['dosen', 'mahasiswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->patch(route('course-sections.status', $courseSection), [
                'is_active' => '0',
            ])->assertForbidden();
        }

        $this->assertDatabaseHas('course_sections', [
            'id' => $courseSection->id,
            'is_active' => true,
        ]);
    }

    public function test_invalid_course_section_status_is_rejected(): void
    {
        $courseSection = CourseSection::query()->create([
            'course_id' => Course::query()->create(['code' => 'CS419', 'name' => 'Kursus Validasi'])->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('course-sections.show', $courseSection))
            ->patch(route('course-sections.status', $courseSection), ['is_active' => 'invalid']);

        $response->assertRedirectToRoute('course-sections.show', $courseSection)
            ->assertSessionHasErrors('is_active');
        $this->assertDatabaseHas('course_sections', [
            'id' => $courseSection->id,
            'is_active' => true,
        ]);
    }

    public function test_missing_course_section_status_target_returns_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch(route('course-sections.status', 999999), [
            'is_active' => '0',
        ])->assertNotFound();
    }

    public function test_inactive_course_is_not_available_and_is_rejected_when_submitted_directly(): void
    {
        $course = Course::query()->create([
            'code' => 'CS403',
            'name' => 'Sistem Terdistribusi',
            'is_active' => false,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('course-sections.create'))
            ->assertOk()
            ->assertDontSeeText('CS403 - Sistem Terdistribusi');

        $response = $this->actingAs($admin)->from(route('course-sections.create'))->post(route('course-sections.store'), [
            'course_id' => $course->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $response->assertRedirectToRoute('course-sections.create')
            ->assertSessionHasErrors('course_id');
        $this->assertDatabaseMissing('course_sections', ['course_id' => $course->id]);
    }

    public function test_required_fields_and_semester_are_validated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('course-sections.create'))
            ->post(route('course-sections.store'), [
                'semester' => 'musim panas',
            ]);

        $response->assertRedirectToRoute('course-sections.create')
            ->assertSessionHasErrors(['course_id', 'name', 'academic_year', 'semester']);
        $this->assertDatabaseCount('course_sections', 0);
    }

    public function test_duplicate_course_section_combination_is_rejected(): void
    {
        $course = Course::query()->create(['code' => 'CS404', 'name' => 'Jaringan Komputer']);
        CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'genap',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->from(route('course-sections.create'))->post(route('course-sections.store'), [
            'course_id' => $course->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'genap',
        ]);

        $response->assertRedirectToRoute('course-sections.create')
            ->assertSessionHasErrors('name');
        $this->assertDatabaseCount('course_sections', 1);
    }

    public function test_course_section_search_matches_section_name_or_course_information(): void
    {
        $firstCourse = Course::query()->create(['code' => 'CS405', 'name' => 'Kecerdasan Buatan']);
        $secondCourse = Course::query()->create(['code' => 'IF406', 'name' => 'Rekayasa Perangkat Lunak']);
        CourseSection::query()->create([
            'course_id' => $firstCourse->id,
            'name' => 'Kelas Pagi',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);
        CourseSection::query()->create([
            'course_id' => $secondCourse->id,
            'name' => 'Kelas Sore',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('course-sections.index', ['search' => 'Pagi']))
            ->assertOk()
            ->assertSeeText('Kelas Pagi')
            ->assertDontSeeText('Kelas Sore');
        $this->actingAs($admin)->get(route('course-sections.index', ['search' => 'Rekayasa']))
            ->assertOk()
            ->assertSeeText('Kelas Sore')
            ->assertDontSeeText('Kelas Pagi');
    }

    public function test_course_section_pagination_displays_ten_records_per_page(): void
    {
        $course = Course::query()->create(['code' => 'CS407', 'name' => 'Matematika Diskrit']);

        foreach (range(1, 12) as $number) {
            CourseSection::query()->create([
                'course_id' => $course->id,
                'name' => "Kelas {$number}",
                'academic_year' => '2026/2027',
                'semester' => $number % 2 === 0 ? 'genap' : 'ganjil',
            ]);
        }
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('course-sections.index'));

        $response->assertOk()
            ->assertViewHas('courseSections', fn ($courseSections): bool => $courseSections->perPage() === 10
                && $courseSections->total() === 12
                && $courseSections->count() === 10);
    }
}

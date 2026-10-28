<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_course_pages(): void
    {
        $this->get(route('courses.index'))->assertRedirectToRoute('login');
        $this->get(route('courses.create'))->assertRedirectToRoute('login');
    }

    public function test_admin_can_view_course_list_and_create_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('courses.index'))
            ->assertOk()
            ->assertSeeText('Daftar Mata Kuliah')
            ->assertSeeText('Tambah Mata Kuliah');
        $this->actingAs($admin)->get(route('courses.create'))
            ->assertOk()
            ->assertSeeText('Tambah Mata Kuliah')
            ->assertSee('name="code"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="description"', false);
    }

    public function test_admin_can_view_course_details_and_edit_form(): void
    {
        $course = Course::query()->create([
            'code' => 'CS301',
            'name' => 'Basis Data',
            'description' => 'Konsep basis data relasional.',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('courses.show', $course))
            ->assertOk()
            ->assertSeeText('Detail Mata Kuliah')
            ->assertSeeText('CS301')
            ->assertSeeText('Basis Data')
            ->assertSeeText('Konsep basis data relasional.')
            ->assertSeeText('Aktif')
            ->assertSee('href="'.route('courses.edit', $course).'"', false);

        $this->actingAs($admin)->get(route('courses.edit', $course))
            ->assertOk()
            ->assertSeeText('Edit Mata Kuliah')
            ->assertSee('name="code"', false)
            ->assertSee('value="CS301"', false)
            ->assertSee('name="name"', false)
            ->assertSee('value="Basis Data"', false)
            ->assertSee('name="description"', false)
            ->assertSee('name="_method"', false)
            ->assertSee('value="PUT"', false);
    }

    public function test_non_admin_users_cannot_access_course_pages(): void
    {
        foreach (['dosen', 'mahasiswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('courses.index'))->assertForbidden();
            $this->actingAs($user)->get(route('courses.create'))->assertForbidden();
        }
    }

    public function test_admin_can_create_a_course(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('courses.store'), [
            'code' => 'CS201',
            'name' => 'Pemrograman Web',
            'description' => 'Mata kuliah pengembangan web.',
        ]);

        $response->assertRedirectToRoute('courses.index')
            ->assertSessionHas('success', 'Mata kuliah berhasil ditambahkan.');
        $this->assertDatabaseHas('courses', [
            'code' => 'CS201',
            'name' => 'Pemrograman Web',
            'description' => 'Mata kuliah pengembangan web.',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_update_a_course(): void
    {
        $course = Course::query()->create([
            'code' => 'CS302',
            'name' => 'Sistem Operasi',
            'description' => 'Deskripsi lama.',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('courses.update', $course), [
            'code' => 'CS303',
            'name' => 'Arsitektur Komputer',
            'description' => 'Deskripsi baru.',
        ]);

        $response->assertRedirectToRoute('courses.show', $course)
            ->assertSessionHas('success', 'Mata kuliah berhasil diperbarui.');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'code' => 'CS303',
            'name' => 'Arsitektur Komputer',
            'description' => 'Deskripsi baru.',
        ]);
    }

    public function test_course_can_keep_its_existing_code_when_updated(): void
    {
        $course = Course::query()->create([
            'code' => 'CS304',
            'name' => 'Pemrograman Dasar',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('courses.update', $course), [
            'code' => 'CS304',
            'name' => 'Pemrograman Dasar Lanjutan',
        ]);

        $response->assertRedirectToRoute('courses.show', $course)
            ->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'code' => 'CS304',
            'name' => 'Pemrograman Dasar Lanjutan',
        ]);
    }

    public function test_duplicate_course_code_is_rejected(): void
    {
        Course::query()->create(['code' => 'CS202', 'name' => 'Mata Kuliah Lama']);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->from(route('courses.create'))->post(route('courses.store'), [
            'code' => 'CS202',
            'name' => 'Mata Kuliah Baru',
        ]);

        $response->assertRedirectToRoute('courses.create')
            ->assertSessionHasErrors('code')
            ->assertSessionHas('_old_input.code', 'CS202');
        $this->assertDatabaseMissing('courses', ['name' => 'Mata Kuliah Baru']);
    }

    public function test_duplicate_course_code_from_another_course_is_rejected_on_update(): void
    {
        $existingCourse = Course::query()->create([
            'code' => 'CS305',
            'name' => 'Kursus Pertama',
        ]);
        $course = Course::query()->create([
            'code' => 'CS306',
            'name' => 'Kursus Kedua',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('courses.edit', $course))
            ->put(route('courses.update', $course), [
                'code' => $existingCourse->code,
                'name' => 'Nama yang Tidak Disimpan',
            ]);

        $response->assertRedirectToRoute('courses.edit', $course)
            ->assertSessionHasErrors('code')
            ->assertSessionHas('_old_input.code', 'CS305');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'code' => 'CS306',
            'name' => 'Kursus Kedua',
        ]);
    }

    public function test_course_code_and_name_are_required(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('courses.store'), []);

        $response->assertSessionHasErrors(['code', 'name']);
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_guest_is_redirected_to_login_from_course_details_and_edit_pages(): void
    {
        $course = Course::query()->create(['code' => 'CS307', 'name' => 'Keamanan Informasi']);

        $this->get(route('courses.show', $course))->assertRedirectToRoute('login');
        $this->get(route('courses.edit', $course))->assertRedirectToRoute('login');
        $this->put(route('courses.update', $course), [
            'code' => 'CS308',
            'name' => 'Keamanan Jaringan',
        ])->assertRedirectToRoute('login');
    }

    public function test_non_admin_users_cannot_access_course_details_or_edit(): void
    {
        $course = Course::query()->create(['code' => 'CS308', 'name' => 'Keamanan Jaringan']);

        foreach (['dosen', 'mahasiswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('courses.show', $course))->assertForbidden();
            $this->actingAs($user)->get(route('courses.edit', $course))->assertForbidden();
            $this->actingAs($user)->put(route('courses.update', $course), [
                'code' => 'CS309',
                'name' => 'Tidak Diizinkan',
            ])->assertForbidden();
        }
    }

    public function test_missing_course_returns_not_found_for_detail_edit_and_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('courses.show', 999999))->assertNotFound();
        $this->actingAs($admin)->get(route('courses.edit', 999999))->assertNotFound();
        $this->actingAs($admin)->put(route('courses.update', 999999), [
            'code' => 'CS310',
            'name' => 'Tidak Ada',
        ])->assertNotFound();
    }

    public function test_admin_can_activate_and_deactivate_a_course_without_deleting_it(): void
    {
        $course = Course::query()->create([
            'code' => 'CS311',
            'name' => 'Manajemen Proyek',
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $deactivation = $this->actingAs($admin)->patch(route('courses.status', $course), [
            'is_active' => '0',
        ]);

        $deactivation->assertRedirectToRoute('courses.show', $course)
            ->assertSessionHas('success', 'Mata kuliah berhasil dinonaktifkan.');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'code' => 'CS311',
            'is_active' => false,
        ]);

        $activation = $this->patch(route('courses.status', $course), [
            'is_active' => '1',
        ]);

        $activation->assertRedirectToRoute('courses.show', $course)
            ->assertSessionHas('success', 'Mata kuliah berhasil diaktifkan.');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'code' => 'CS311',
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_from_changing_course_status(): void
    {
        $course = Course::query()->create(['code' => 'CS312', 'name' => 'Etika Profesi']);

        $this->patch(route('courses.status', $course), ['is_active' => '0'])
            ->assertRedirectToRoute('login');
    }

    public function test_non_admin_users_cannot_change_course_status(): void
    {
        $course = Course::query()->create(['code' => 'CS313', 'name' => 'Kewirausahaan']);

        foreach (['dosen', 'mahasiswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->patch(route('courses.status', $course), [
                'is_active' => '0',
            ])->assertForbidden();
        }

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'is_active' => true,
        ]);
    }

    public function test_missing_course_status_target_returns_not_found(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch(route('courses.status', 999999), [
            'is_active' => '0',
        ])->assertNotFound();
    }

    public function test_invalid_course_status_is_rejected(): void
    {
        $course = Course::query()->create([
            'code' => 'CS314',
            'name' => 'Komunikasi Bisnis',
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('courses.show', $course))
            ->patch(route('courses.status', $course), ['is_active' => 'invalid']);

        $response->assertRedirectToRoute('courses.show', $course)
            ->assertSessionHasErrors('is_active');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'is_active' => true,
        ]);
    }

    public function test_course_search_matches_code_or_name(): void
    {
        Course::query()->create(['code' => 'CS203', 'name' => 'Jaringan Komputer']);
        Course::query()->create(['code' => 'IF204', 'name' => 'Pemrograman Mobile']);
        Course::query()->create(['code' => 'SI205', 'name' => 'Analisis Sistem']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('courses.index', ['search' => 'CS203']))
            ->assertOk()
            ->assertSeeText('Jaringan Komputer')
            ->assertDontSeeText('Pemrograman Mobile')
            ->assertDontSeeText('Analisis Sistem');
        $this->actingAs($admin)->get(route('courses.index', ['search' => 'Mobile']))
            ->assertOk()
            ->assertSeeText('Pemrograman Mobile')
            ->assertDontSeeText('Jaringan Komputer')
            ->assertDontSeeText('Analisis Sistem');
    }

    public function test_course_pagination_displays_ten_records_per_page(): void
    {
        foreach (range(1, 12) as $number) {
            Course::query()->create([
                'code' => sprintf('CS%03d', $number),
                'name' => "Mata Kuliah {$number}",
            ]);
        }
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('courses.index'));

        $response->assertOk()
            ->assertViewHas('courses', fn ($courses): bool => $courses->perPage() === 10
                && $courses->total() === 12
                && $courses->count() === 10);
    }
}

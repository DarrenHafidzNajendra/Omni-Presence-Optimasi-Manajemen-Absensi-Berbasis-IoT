<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
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
        $academicPeriod = $this->createAcademicPeriod();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('course-sections.index'))
            ->assertOk()
            ->assertSeeText('Daftar Kelas')
            ->assertSeeText('Tambah Kelas')
            ->assertSee('href="'.route('academic-periods.index').'"', false);

        $this->actingAs($admin)->get(route('course-sections.create'))
            ->assertOk()
            ->assertSeeText('Tambah Kelas')
            ->assertSeeText('CS401 - Analisis Data')
            ->assertSee('name="course_id"', false)
            ->assertSee('name="academic_period_id"', false)
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
            ->assertSee('href="'.route('course-sections.attendance-sessions.index', $courseSection).'"', false)
            ->assertSeeText('Kelola Sesi Absensi')
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
        $student = User::factory()->create(['role' => 'mahasiswa']);
        $this->actingAs($student)->get(route('course-sections.index'))->assertForbidden();
        $this->actingAs($student)->get(route('course-sections.create'))->assertForbidden();
        $this->actingAs($student)->post(route('course-sections.store'))->assertForbidden();

        foreach (['dosen', 'mahasiswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);

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
        $academicPeriod = $this->createAcademicPeriod();
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('course-sections.store'), [
            'course_id' => $course->id,
            'academic_period_id' => $academicPeriod->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        $response->assertRedirectToRoute('course-sections.index')
            ->assertSessionHas('success', 'Kelas berhasil ditambahkan.');
        $this->assertDatabaseHas('course_sections', [
            'course_id' => $course->id,
            'academic_period_id' => $academicPeriod->id,
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
        $academicPeriod = $this->createAcademicPeriod();
        $courseSection = CourseSection::query()->create([
            'course_id' => $oldCourse->id,
            'name' => 'Kelas Lama',
            'academic_year' => '2025/2026',
            'semester' => 'ganjil',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('course-sections.update', $courseSection), [
            'course_id' => $newCourse->id,
            'academic_period_id' => $academicPeriod->id,
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
        $academicPeriod = $this->createAcademicPeriod();
        $courseSection = CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'Kelas A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->put(route('course-sections.update', $courseSection), [
            'course_id' => $course->id,
            'academic_period_id' => $academicPeriod->id,
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
            ->assertSessionHasErrors(['course_id', 'academic_period_id', 'name', 'academic_year', 'semester']);
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

    public function test_course_section_can_be_created_with_an_active_academic_period_and_relationships_are_available(): void
    {
        $course = Course::query()->create(['code' => 'CS420', 'name' => 'Pemrograman Web']);
        $academicPeriod = $this->createAcademicPeriod();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('course-sections.store'), [
            'course_id' => $course->id,
            'academic_period_id' => $academicPeriod->id,
            'name' => 'Kelas Relasi',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ])->assertRedirectToRoute('course-sections.index');

        $courseSection = CourseSection::query()->latest('id')->firstOrFail();

        $this->assertTrue($courseSection->academicPeriod->is($academicPeriod));
        $this->assertTrue($academicPeriod->courseSections->contains($courseSection));
    }

    public function test_inactive_academic_period_is_rejected_for_new_course_sections(): void
    {
        $course = Course::query()->create(['code' => 'CS421', 'name' => 'Basis Data']);
        $academicPeriod = $this->createAcademicPeriod(['is_active' => false]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('course-sections.create'))
            ->post(route('course-sections.store'), [
                'course_id' => $course->id,
                'academic_period_id' => $academicPeriod->id,
                'name' => 'Kelas Ditolak',
                'academic_year' => '2026/2027',
                'semester' => 'ganjil',
            ]);

        $response->assertRedirectToRoute('course-sections.create')
            ->assertSessionHasErrors('academic_period_id');
        $this->assertDatabaseMissing('course_sections', ['name' => 'Kelas Ditolak']);
    }

    public function test_existing_course_section_keeps_an_inactive_academic_period(): void
    {
        $course = Course::query()->create(['code' => 'CS422', 'name' => 'Sistem Operasi']);
        $academicPeriod = $this->createAcademicPeriod(['is_active' => false]);
        $courseSection = CourseSection::query()->create([
            'course_id' => $course->id,
            'academic_period_id' => $academicPeriod->id,
            'name' => 'Kelas Lama',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('course-sections.edit', $courseSection))
            ->assertOk()
            ->assertSee($academicPeriod->name, false)
            ->assertSee('value="'.$academicPeriod->id.'" selected', false);

        $this->assertDatabaseHas('course_sections', [
            'id' => $courseSection->id,
            'academic_period_id' => $academicPeriod->id,
        ]);
    }

    public function test_course_section_index_renders_attendance_session_link(): void
    {
        $course = Course::query()->create(['code' => 'CS423', 'name' => 'Jaringan Komputer']);
        $period = $this->createAcademicPeriod();
        $section = CourseSection::query()->create([
            'course_id' => $course->id,
            'academic_period_id' => $period->id,
            'name' => 'Kelas TI-3A',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('course-sections.index'));

        $response->assertOk()
            ->assertSeeText('Absensi')
            ->assertSee(route('course-sections.attendance-sessions.index', $section), false);
    }

    public function test_admin_can_filter_course_sections_by_academic_period_course_lecturer_and_section(): void
    {
        $activePeriod = $this->createAcademicPeriod([
            'name' => 'Semester Ganjil 2026/2027',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);

        $oldPeriod = $this->createAcademicPeriod([
            'name' => 'Semester Genap 2025/2026',
            'academic_year' => '2025/2026',
            'semester' => 'genap',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-07-31',
            'is_active' => false,
        ]);

        $courseA = Course::query()->create(['code' => 'IF501', 'name' => 'Pemrograman IoT']);
        $courseB = Course::query()->create(['code' => 'IF502', 'name' => 'Visi Komputer']);

        $lecturerA = User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Alpha']);
        $lecturerB = User::factory()->create(['role' => 'dosen', 'name' => 'Dosen Beta']);

        $sectionActive = CourseSection::query()->create([
            'course_id' => $courseA->id,
            'academic_period_id' => $activePeriod->id,
            'name' => 'TI-3A Aktif',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
        $sectionActive->lecturers()->create([
            'user_id' => $lecturerA->id,
            'teaching_type' => 'teori',
        ]);
        $sectionActive->schedules()->create([
            'session_type' => 'teori',
            'delivery_mode' => 'onsite',
            'day_of_week' => 1,
            'starts_at' => '08:00',
            'ends_at' => '10:00',
            'timezone' => 'Asia/Jakarta',
            'location' => 'Lab IoT 301',
            'is_active' => true,
        ]);

        $sectionOld = CourseSection::query()->create([
            'course_id' => $courseB->id,
            'academic_period_id' => $oldPeriod->id,
            'name' => 'TI-2B Lama',
            'academic_year' => '2025/2026',
            'semester' => 'genap',
            'is_active' => true,
        ]);
        $sectionOld->lecturers()->create([
            'user_id' => $lecturerB->id,
            'teaching_type' => 'praktikum',
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        // 1. Default view uses active academic period and displays schedule/lecturer columns
        $this->actingAs($admin)->get(route('course-sections.index'))
            ->assertOk()
            ->assertSeeText('TI-3A Aktif')
            ->assertSeeText('Dosen Alpha')
            ->assertSeeText('Senin')
            ->assertSeeText('08:00 - 10:00')
            ->assertSeeText('Lab IoT 301')
            ->assertDontSeeText('TI-2B Lama');

        // 2. Filtering by old academic period shows old semester section
        $this->actingAs($admin)->get(route('course-sections.index', ['academic_period_id' => $oldPeriod->id]))
            ->assertOk()
            ->assertSeeText('TI-2B Lama')
            ->assertSeeText('Dosen Beta')
            ->assertDontSeeText('TI-3A Aktif');

        // 3. Filtering by 'all' academic periods and lecturer_id
        $this->actingAs($admin)->get(route('course-sections.index', [
            'academic_period_id' => 'all',
            'lecturer_id' => $lecturerB->id,
        ]))
            ->assertOk()
            ->assertSeeText('TI-2B Lama')
            ->assertDontSeeText('TI-3A Aktif');

        // 4. Filtering by course_id and course_section_id
        $this->actingAs($admin)->get(route('course-sections.index', [
            'academic_period_id' => 'all',
            'course_id' => $courseA->id,
            'course_section_id' => $sectionActive->id,
        ]))
            ->assertOk()
            ->assertSeeText('TI-3A Aktif')
            ->assertDontSeeText('TI-2B Lama');
    }

    public function test_course_section_show_displays_enrolled_students_summary_search_and_ten_per_page_pagination(): void
    {
        $course = Course::query()->create(['code' => 'IF510', 'name' => 'Pemrograman Jaringan']);
        $courseSection = CourseSection::query()->create([
            'course_id' => $course->id,
            'name' => 'Kelas TI-3B',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
        ]);

        foreach (range(1, 12) as $index) {
            $student = User::factory()->create([
                'name' => sprintf('Mahasiswa Peserta %02d', $index),
                'nim' => sprintf('2026510%03d', $index),
                'role' => 'mahasiswa',
                'is_active' => true,
            ]);

            $courseSection->enrollments()->create([
                'student_id' => $student->id,
                'status' => 'aktif',
            ]);
        }

        $admin = User::factory()->create(['role' => 'admin']);

        // 1. Halaman 1 menampilkan ringkasan 12 mahasiswa terdaftar dan maksimal 10 mahasiswa per halaman
        $pageOneResponse = $this->actingAs($admin)->get(route('course-sections.show', $courseSection));

        $pageOneResponse
            ->assertOk()
            ->assertSee('data-enrollment-summary', false)
            ->assertSee('data-enrollment-pagination', false)
            ->assertSeeText('12 Mahasiswa Terdaftar')
            ->assertSeeText('Mahasiswa Peserta 01')
            ->assertSeeText('Mahasiswa Peserta 10')
            ->assertDontSeeText('Mahasiswa Peserta 11')
            ->assertDontSeeText('Mahasiswa Peserta 12')
            ->assertViewHas('totalEnrolledCount', 12)
            ->assertViewHas('enrollments', fn ($enrollments): bool => $enrollments->perPage() === 10
                && $enrollments->total() === 12
                && $enrollments->count() === 10);

        // 2. Halaman 2 menampilkan sisa 2 mahasiswa
        $this->actingAs($admin)->get(route('course-sections.show', [
            'courseSection' => $courseSection,
            'page' => 2,
        ]))
            ->assertOk()
            ->assertSeeText('12 Mahasiswa Terdaftar')
            ->assertSeeText('Mahasiswa Peserta 11')
            ->assertSeeText('Mahasiswa Peserta 12')
            ->assertDontSeeText('Mahasiswa Peserta 01')
            ->assertViewHas('enrollments', fn ($enrollments): bool => $enrollments->count() === 2);

        // 3. Pencarian berdasarkan nama atau NIM memfilter daftar dan tetap menampilkan jumlah total terdaftar
        $this->actingAs($admin)->get(route('course-sections.show', [
            'courseSection' => $courseSection,
            'student_search' => '2026510011',
        ]))
            ->assertOk()
            ->assertSeeText('12 Mahasiswa Terdaftar')
            ->assertSeeText('1 sesuai pencarian')
            ->assertSeeText('Mahasiswa Peserta 11')
            ->assertDontSeeText('Mahasiswa Peserta 01')
            ->assertViewHas('totalEnrolledCount', 12)
            ->assertViewHas('enrollments', fn ($enrollments): bool => $enrollments->total() === 1);

        // 4. Pencarian yang tidak cocok menampilkan pesan kosong yang sesuai
        $this->actingAs($admin)->get(route('course-sections.show', [
            'courseSection' => $courseSection,
            'student_search' => 'TIDAK-ADA-999',
        ]))
            ->assertOk()
            ->assertSeeText('12 Mahasiswa Terdaftar')
            ->assertSeeText('0 sesuai pencarian')
            ->assertSeeText('Tidak ada mahasiswa terdaftar yang sesuai pencarian.');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAcademicPeriod(array $overrides = []): AcademicPeriod
    {
        return AcademicPeriod::query()->create(array_merge([
            'name' => 'Semester Ganjil 2026/2027',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'starts_on' => '2026-08-01',
            'ends_on' => '2027-01-31',
            'ats_starts_on' => '2026-10-05',
            'ats_ends_on' => '2026-10-16',
            'uas_starts_on' => '2027-01-11',
            'uas_ends_on' => '2027-01-22',
            'replacement_deadline' => '2027-01-29',
            'is_active' => true,
        ], $overrides));
    }
}

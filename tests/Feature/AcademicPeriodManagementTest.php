<?php

namespace Tests\Feature;

use App\Models\AcademicPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicPeriodManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_academic_period_management(): void
    {
        $this->get(route('academic-periods.index'))->assertRedirectToRoute('login');
        $this->get(route('academic-periods.create'))->assertRedirectToRoute('login');
        $this->post(route('academic-periods.store'))->assertRedirectToRoute('login');
    }

    public function test_only_admin_can_access_academic_period_management(): void
    {
        foreach (['dosen', 'mahasiswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('academic-periods.index'))->assertForbidden();
            $this->actingAs($user)->get(route('academic-periods.create'))->assertForbidden();
        }
    }

    public function test_admin_can_create_an_academic_period(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('academic-periods.store'), $this->periodData());

        $response->assertRedirectToRoute('academic-periods.index')
            ->assertSessionHas('success', 'Periode akademik berhasil ditambahkan.');
        $this->assertDatabaseHas('academic_periods', [
            'name' => 'Semester Ganjil 2026/2027',
            'academic_year' => '2026/2027',
            'semester' => 'ganjil',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_and_update_an_academic_period(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $academicPeriod = AcademicPeriod::query()->create($this->periodData());

        $this->actingAs($admin)->get(route('academic-periods.show', $academicPeriod))
            ->assertOk()
            ->assertSeeText('Semester Ganjil 2026/2027');

        $this->actingAs($admin)->put(route('academic-periods.update', $academicPeriod), $this->periodData([
            'name' => 'Semester Genap 2026/2027',
            'semester' => 'genap',
        ]))->assertRedirectToRoute('academic-periods.show', $academicPeriod);

        $this->assertDatabaseHas('academic_periods', [
            'id' => $academicPeriod->id,
            'name' => 'Semester Genap 2026/2027',
            'semester' => 'genap',
        ]);
    }

    public function test_academic_period_dates_must_be_consistent(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('academic-periods.create'))
            ->post(route('academic-periods.store'), $this->periodData([
                'ends_on' => '2026-01-01',
                'ats_starts_on' => '2025-08-01',
                'replacement_deadline' => '2027-01-01',
            ]));

        $response->assertRedirectToRoute('academic-periods.create')
            ->assertSessionHasErrors(['ends_on', 'ats_starts_on', 'replacement_deadline']);
        $this->assertDatabaseCount('academic_periods', 0);
    }

    public function test_ats_and_uas_ranges_cannot_be_reversed_or_overlap(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('academic-periods.create'))
            ->post(route('academic-periods.store'), $this->periodData([
                'ats_ends_on' => '2026-10-01',
                'uas_starts_on' => '2026-09-30',
            ]));

        $response->assertRedirectToRoute('academic-periods.create')
            ->assertSessionHasErrors('uas_starts_on');
        $this->assertDatabaseCount('academic_periods', 0);
    }

    public function test_date_format_is_enforced(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('academic-periods.create'))
            ->post(route('academic-periods.store'), $this->periodData([
                'starts_on' => '2026-1-01',
            ]));

        $response->assertRedirectToRoute('academic-periods.create')
            ->assertSessionHasErrors('starts_on');
    }

    public function test_admin_can_store_academic_period_with_replacement_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('academic-periods.store'), $this->periodData([
            'ats_replacement_starts_on' => '2026-10-20',
            'ats_replacement_ends_on' => '2026-10-25',
            'uas_replacement_starts_on' => '2027-01-25',
            'uas_replacement_ends_on' => '2027-01-29',
        ]));

        $response->assertRedirectToRoute('academic-periods.index');
        $this->assertDatabaseHas('academic_periods', [
            'name' => 'Semester Ganjil 2026/2027',
        ]);
        $period = AcademicPeriod::query()->where('name', 'Semester Ganjil 2026/2027')->firstOrFail();
        $this->assertSame('2026-10-20', $period->ats_replacement_starts_on->format('Y-m-d'));
        $this->assertSame('2026-10-25', $period->ats_replacement_ends_on->format('Y-m-d'));
        $this->assertSame('2027-01-25', $period->uas_replacement_starts_on->format('Y-m-d'));
        $this->assertSame('2027-01-29', $period->uas_replacement_ends_on->format('Y-m-d'));
    }

    public function test_admin_can_store_academic_period_without_replacement_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('academic-periods.store'), $this->periodData());

        $response->assertRedirectToRoute('academic-periods.index');
        $this->assertDatabaseHas('academic_periods', [
            'name' => 'Semester Ganjil 2026/2027',
            'ats_replacement_starts_on' => null,
            'ats_replacement_ends_on' => null,
            'uas_replacement_starts_on' => null,
            'uas_replacement_ends_on' => null,
        ]);
    }

    public function test_ats_replacement_end_cannot_be_before_start(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('academic-periods.create'))
            ->post(route('academic-periods.store'), $this->periodData([
                'ats_replacement_starts_on' => '2026-10-25',
                'ats_replacement_ends_on' => '2026-10-20',
            ]));

        $response->assertRedirectToRoute('academic-periods.create')
            ->assertSessionHasErrors('ats_replacement_ends_on');
        $this->assertDatabaseCount('academic_periods', 0);
    }

    public function test_uas_replacement_end_cannot_be_before_start(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('academic-periods.create'))
            ->post(route('academic-periods.store'), $this->periodData([
                'uas_replacement_starts_on' => '2027-01-29',
                'uas_replacement_ends_on' => '2027-01-25',
            ]));

        $response->assertRedirectToRoute('academic-periods.create')
            ->assertSessionHasErrors('uas_replacement_ends_on');
        $this->assertDatabaseCount('academic_periods', 0);
    }

    public function test_replacement_dates_must_be_within_semester(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->from(route('academic-periods.create'))
            ->post(route('academic-periods.store'), $this->periodData([
                'ats_replacement_starts_on' => '2025-01-01',
                'ats_replacement_ends_on' => '2025-01-10',
            ]));

        $response->assertRedirectToRoute('academic-periods.create')
            ->assertSessionHasErrors(['ats_replacement_starts_on', 'ats_replacement_ends_on']);
        $this->assertDatabaseCount('academic_periods', 0);
    }

    public function test_show_displays_replacement_dates_when_set(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $academicPeriod = AcademicPeriod::query()->create($this->periodData([
            'ats_replacement_starts_on' => '2026-10-20',
            'ats_replacement_ends_on' => '2026-10-25',
            'uas_replacement_starts_on' => '2027-01-25',
            'uas_replacement_ends_on' => '2027-01-29',
        ]));

        $this->actingAs($admin)
            ->get(route('academic-periods.show', $academicPeriod))
            ->assertOk()
            ->assertSeeText('Pengganti ATS')
            ->assertSeeText('20/10/2026')
            ->assertSeeText('Pengganti UAS')
            ->assertSeeText('25/01/2027');
    }

    public function test_show_hides_replacement_section_when_dates_are_null(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $academicPeriod = AcademicPeriod::query()->create($this->periodData());

        $this->actingAs($admin)
            ->get(route('academic-periods.show', $academicPeriod))
            ->assertOk()
            ->assertDontSeeText('Pengganti ATS')
            ->assertDontSeeText('Pengganti UAS');
    }

    public function test_edit_form_shows_saved_replacement_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $academicPeriod = AcademicPeriod::query()->create($this->periodData([
            'ats_replacement_starts_on' => '2026-10-20',
            'ats_replacement_ends_on' => '2026-10-25',
        ]));

        $this->actingAs($admin)
            ->get(route('academic-periods.edit', $academicPeriod))
            ->assertOk()
            ->assertSee('2026-10-20')
            ->assertSee('2026-10-25');
    }

    public function test_admin_can_change_academic_period_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $academicPeriod = AcademicPeriod::query()->create($this->periodData());

        $this->actingAs($admin)
            ->patch(route('academic-periods.status', $academicPeriod), ['is_active' => '0'])
            ->assertRedirectToRoute('academic-periods.show', $academicPeriod);

        $this->assertDatabaseHas('academic_periods', [
            'id' => $academicPeriod->id,
            'is_active' => false,
        ]);
    }

    public function test_academic_period_form_groups_fields_and_shows_hints(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('academic-periods.create'));

        $response->assertOk()
            ->assertSeeText('1. Informasi Periode Akademik')
            ->assertSeeText('2. Jadwal ATS dan UAS')
            ->assertSeeText('3. Jadwal Minggu Pengganti')
            ->assertSeeText('Minggu pengganti berlangsung hari Senin–Jumat pada satu minggu perkuliahan sebelum ATS atau UAS.')
            ->assertSeeText('Senin dalam satu minggu perkuliahan sebelum ATS.')
            ->assertSeeText('Jumat dalam satu minggu perkuliahan sebelum ATS.')
            ->assertSeeText('Senin dalam satu minggu perkuliahan sebelum UAS.')
            ->assertSeeText('Jumat dalam satu minggu perkuliahan sebelum UAS.');
    }

    /**
     * @return array<string, mixed>
     */
    private function periodData(array $overrides = []): array
    {
        return array_merge([
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
        ], $overrides);
    }
}

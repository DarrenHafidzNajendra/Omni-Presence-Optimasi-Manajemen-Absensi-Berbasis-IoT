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
            ->assertSeeText('Semester Ganjil 2026/2027')
            ->assertSeeText('Batas Kelas Pengganti');

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

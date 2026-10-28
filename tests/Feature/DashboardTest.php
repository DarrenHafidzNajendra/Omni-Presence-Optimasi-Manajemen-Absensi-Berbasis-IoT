<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_sections_with_explicit_empty_states(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()
            ->assertSeeText('Statistik pengguna')
            ->assertSeeText('Total pengguna')
            ->assertSeeText('Admin')
            ->assertSeeText('Dosen')
            ->assertSeeText('Mahasiswa')
            ->assertSeeText('Statistik kehadiran')
            ->assertSeeText('Belum ada data koneksi')
            ->assertSeeText('QR Code')
            ->assertSeeText('Face Recognition')
            ->assertSeeText('Aktivitas terbaru')
            ->assertSeeText('Belum ada aktivitas')
            ->assertSeeText('Data belum tersedia');
    }

    public function test_dashboard_user_statistics_reflect_database_counts_and_update_when_users_change(): void
    {
        User::factory()->count(2)->create(['role' => UserRole::Admin->value]);
        User::factory()->count(1)->create(['role' => UserRole::Dosen->value]);
        User::factory()->count(3)->create(['role' => UserRole::Mahasiswa->value]);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $initialResponse = $this->actingAs($admin)->get(route('dashboard'));

        $initialResponse->assertOk()
            ->assertSee('data-statistic="total">7', false)
            ->assertSee('data-statistic="admin">3', false)
            ->assertSee('data-statistic="dosen">1', false)
            ->assertSee('data-statistic="mahasiswa">3', false);

        User::factory()->count(2)->create(['role' => UserRole::Dosen->value]);

        $updatedResponse = $this->get(route('dashboard'));

        $updatedResponse->assertOk()
            ->assertSee('data-statistic="total">9', false)
            ->assertSee('data-statistic="admin">3', false)
            ->assertSee('data-statistic="dosen">3', false)
            ->assertSee('data-statistic="mahasiswa">3', false);
    }

    public function test_dashboard_hides_user_statistics_from_non_admin_roles(): void
    {
        $dosen = User::factory()->create(['role' => UserRole::Dosen->value]);

        $response = $this->actingAs($dosen)->get(route('dashboard'));

        $response->assertOk()
            ->assertDontSeeText('Statistik pengguna')
            ->assertSeeText('Statistik kehadiran');
    }
}

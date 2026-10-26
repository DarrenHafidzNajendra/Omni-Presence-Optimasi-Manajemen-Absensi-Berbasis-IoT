<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_displays_sections_with_explicit_empty_states(): void
    {
        $user = User::factory()->make();
        $user->forceFill(['role' => 'admin']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk()
            ->assertSeeText('Statistik kehadiran')
            ->assertSeeText('Belum ada data koneksi')
            ->assertSeeText('QR Code')
            ->assertSeeText('Face Recognition')
            ->assertSeeText('Aktivitas terbaru')
            ->assertSeeText('Belum ada aktivitas')
            ->assertSeeText('Data belum tersedia');
    }
}

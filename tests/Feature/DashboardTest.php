<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_homepage_redirects_to_the_named_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirectToRoute('dashboard');
    }

    public function test_dashboard_displays_sections_with_explicit_empty_states(): void
    {
        $response = $this->get('/dashboard');

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

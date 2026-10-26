<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_all_management_navigation_links(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertSeeText('Dashboard')
            ->assertSeeText('Pengguna')
            ->assertSeeText('Mata Kuliah')
            ->assertSeeText('Kelas Mata Kuliah')
            ->assertSee('id="app-sidebar"', false)
            ->assertSee('id="sidebar-overlay"', false)
            ->assertSee('data-sidebar-open', false)
            ->assertSee('data-sidebar-close', false)
            ->assertSee('href="'.route('users.index').'"', false)
            ->assertSee('href="'.route('courses.index').'"', false)
            ->assertSee('href="'.route('course-sections.index').'"', false);
    }

    public function test_admin_navigation_marks_the_current_management_section_as_active(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $coursesResponse = $this->actingAs($admin)->get(route('courses.index'));
        $coursesResponse->assertOk()
            ->assertSeeText('Mata Kuliah')
            ->assertSee('href="'.route('courses.index').'"', false)
            ->assertSee('aria-current="page"', false);

        $courseSectionsResponse = $this->actingAs($admin)->get(route('course-sections.index'));
        $courseSectionsResponse->assertOk()
            ->assertSeeText('Kelas Mata Kuliah')
            ->assertSee('href="'.route('course-sections.index').'"', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_non_admin_users_do_not_see_admin_management_navigation_links(): void
    {
        foreach (['dosen', 'mahasiswa'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertDontSeeText('Pengguna')
                ->assertDontSeeText('Mata Kuliah')
                ->assertDontSeeText('Kelas Mata Kuliah');
        }
    }
}

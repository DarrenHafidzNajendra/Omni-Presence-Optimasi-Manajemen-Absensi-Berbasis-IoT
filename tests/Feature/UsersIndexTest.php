<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_the_users_list(): void
    {
        $response = $this->get(route('users.index'));

        $response->assertRedirectToRoute('login');
    }

    public function test_non_admin_user_is_forbidden_from_the_users_list(): void
    {
        $user = User::factory()->create(['role' => 'dosen']);

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_users_and_the_active_navigation_link(): void
    {
        User::factory()->create([
            'name' => 'Ayu Putri',
            'email' => 'ayu@example.com',
            'nidn' => 'NIDN-001',
            'role' => 'dosen',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk()
            ->assertSeeText('Tambah Pengguna')
            ->assertSee('href="'.route('users.create').'"', false)
            ->assertSeeText('Ayu Putri')
            ->assertSeeText('NIDN-001')
            ->assertSeeText('Dosen')
            ->assertSee('href="'.route('users.index').'"', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_search_matches_role_specific_identifiers(): void
    {
        User::factory()->create([
            'name' => 'Rina Wulandari',
            'email' => null,
            'nidn' => 'NIDN-1001',
            'role' => 'dosen',
        ]);
        User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => null,
            'nim' => 'NIM-1001',
            'role' => 'mahasiswa',
        ]);
        User::factory()->create([
            'name' => 'Nama Lain',
            'email' => null,
            'nim' => 'NIM-1002',
            'role' => 'mahasiswa',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('users.index', ['search' => 'NIM-1001']));

        $response->assertOk()
            ->assertSeeText('Budi Santoso')
            ->assertDontSeeText('Rina Wulandari')
            ->assertDontSeeText('Nama Lain');
    }

    public function test_search_matches_admin_email(): void
    {
        User::factory()->create([
            'name' => 'Admin Utama',
            'email' => 'primary-admin@example.com',
            'role' => 'admin',
        ]);
        User::factory()->create([
            'name' => 'Admin Lain',
            'email' => 'other-admin@example.com',
            'role' => 'admin',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('users.index', [
            'search' => 'primary-admin@example.com',
        ]));

        $response->assertOk()
            ->assertSeeText('Admin Utama')
            ->assertDontSeeText('Admin Lain');
    }

    public function test_table_displays_the_identity_for_each_users_role(): void
    {
        User::factory()->create([
            'name' => 'Admin Identitas',
            'email' => 'admin-identity@example.com',
            'role' => 'admin',
        ]);
        User::factory()->create([
            'name' => 'Dosen Identitas',
            'email' => null,
            'nidn' => 'NIDN-4001',
            'role' => 'dosen',
        ]);
        User::factory()->create([
            'name' => 'Mahasiswa Identitas',
            'email' => null,
            'nim' => 'NIM-4001',
            'role' => 'mahasiswa',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk()
            ->assertSeeText('admin-identity@example.com')
            ->assertSeeText('NIDN-4001')
            ->assertSeeText('NIM-4001');
    }

    public function test_search_matches_user_name(): void
    {
        User::factory()->create([
            'name' => 'Rina Wulandari',
            'email' => null,
            'nidn' => 'NIDN-2001',
            'role' => 'dosen',
        ]);
        User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => null,
            'nim' => 'NIM-2001',
            'role' => 'mahasiswa',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('users.index', ['search' => 'Rina']));

        $response->assertOk()
            ->assertSeeText('Rina Wulandari')
            ->assertDontSeeText('Budi Santoso');
    }

    public function test_role_filter_only_shows_users_with_the_selected_role(): void
    {
        User::factory()->create([
            'name' => 'Dosen Aktif',
            'email' => null,
            'nidn' => 'NIDN-3001',
            'role' => 'dosen',
        ]);
        User::factory()->create([
            'name' => 'Mahasiswa Aktif',
            'email' => null,
            'nim' => 'NIM-3001',
            'role' => 'mahasiswa',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('users.index', ['role' => 'dosen']));

        $response->assertOk()
            ->assertSeeText('Dosen Aktif')
            ->assertDontSeeText('Mahasiswa Aktif');
    }

    public function test_pagination_preserves_search_and_role_filters(): void
    {
        User::factory()
            ->count(11)
            ->sequence(fn (Sequence $sequence): array => [
                'name' => sprintf('Student %02d', $sequence->index + 1),
                'email' => null,
                'nim' => sprintf('NIM-%02d', $sequence->index + 1),
                'role' => 'mahasiswa',
            ])
            ->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('users.index', [
            'search' => 'NIM-',
            'role' => 'mahasiswa',
            'page' => 2,
        ]));

        $response->assertOk()
            ->assertSee('search=NIM-', false)
            ->assertSee('role=mahasiswa', false)
            ->assertSee('page=1', false)
            ->assertSeeText('Student 11')
            ->assertDontSeeText('Student 01')
            ->assertViewHas('users', fn ($users): bool => $users->currentPage() === 2 && $users->perPage() === 10);
    }

    public function test_users_with_missing_or_unknown_roles_have_a_safe_label(): void
    {
        User::factory()->create([
            'name' => 'Role Kosong',
            'role' => null,
        ]);
        User::factory()->create([
            'name' => 'Role Tidak Dikenal',
            'role' => 'operator',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk()
            ->assertSeeText('Role Kosong')
            ->assertSeeText('Role Tidak Dikenal')
            ->assertSeeText('Belum ditentukan');
    }

    public function test_empty_search_results_include_a_link_to_all_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('users.index', ['search' => 'tidak-ada']));

        $response->assertOk()
            ->assertSeeText('Pengguna tidak ditemukan')
            ->assertSee('href="'.route('users.index').'"', false)
            ->assertSeeText('Lihat semua pengguna');
    }

    public function test_non_admin_navigation_does_not_show_the_users_link(): void
    {
        $user = User::factory()->create(['role' => 'mahasiswa']);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()->assertDontSeeText('Pengguna');
    }
}

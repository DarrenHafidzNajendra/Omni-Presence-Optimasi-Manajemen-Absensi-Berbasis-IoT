<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_are_active_by_default_and_status_is_cast_to_boolean(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Akun Default Aktif',
            'password' => bcrypt('password'),
            'role' => UserRole::Admin->value,
        ]);
        $user = User::query()->findOrFail($userId);

        $this->assertTrue($user->is_active);
        $this->assertIsBool($user->is_active);
    }

    public function test_admin_can_deactivate_and_reactivate_an_account_without_deleting_it(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $user = User::factory()->create([
            'email' => null,
            'nidn' => '001234',
            'nim' => null,
            'role' => UserRole::Dosen->value,
        ]);

        $deactivation = $this->actingAs($admin)->patch(route('users.status', $user), [
            'is_active' => '0',
        ]);

        $deactivation->assertRedirectToRoute('users.index')
            ->assertSessionHas('success', 'Akun pengguna berhasil dinonaktifkan.');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
            'nidn' => '001234',
            'role' => UserRole::Dosen->value,
        ]);
        $this->assertModelExists($user);

        $activation = $this->patch(route('users.status', $user), [
            'is_active' => '1',
        ]);

        $activation->assertRedirectToRoute('users.index')
            ->assertSessionHas('success', 'Akun pengguna berhasil diaktifkan.');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function test_reactivated_user_can_log_in_again(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $user = User::factory()->create([
            'email' => null,
            'nidn' => '001234',
            'is_active' => false,
            'role' => UserRole::Dosen->value,
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->patch(route('users.status', $user), ['is_active' => '1'])
            ->assertRedirectToRoute('users.index');
        $this->post(route('logout'))->assertRedirectToRoute('login');

        $response = $this->post(route('login'), [
            'role' => UserRole::Dosen->value,
            'identity' => '001234',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->from(route('users.index'))->patch(route('users.status', $admin), [
            'is_active' => '0',
        ]);

        $response->assertRedirectToRoute('users.index')
            ->assertSessionHasErrors([
                'is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.',
            ]);
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => true,
        ]);
    }

    /**
     * @return array<string, array{UserRole}>
     */
    public static function nonAdminRoles(): array
    {
        return [
            'dosen' => [UserRole::Dosen],
            'mahasiswa' => [UserRole::Mahasiswa],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_cannot_change_an_account_status(UserRole $role): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $actor = User::factory()->create(['role' => $role->value]);

        $response = $this->actingAs($actor)->patch(route('users.status', $admin), [
            'is_active' => '0',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'is_active' => true,
        ]);
    }

    public function test_guest_is_redirected_from_changing_an_account_status(): void
    {
        $user = User::factory()->create();

        $response = $this->patch(route('users.status', $user), [
            'is_active' => '0',
        ]);

        $response->assertRedirectToRoute('login');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
        ]);
    }

    public function test_status_route_returns_not_found_for_a_missing_user(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->patch(route('users.status', 999999), [
            'is_active' => '0',
        ]);

        $response->assertNotFound();
    }

    public function test_users_list_displays_account_status_and_does_not_offer_self_deactivation(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $inactiveUser = User::factory()->create(['is_active' => false]);
        $activeUser = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk()
            ->assertSeeText('Aktif')
            ->assertSeeText('Nonaktif')
            ->assertSeeText('Akun Anda')
            ->assertSee('action="'.route('users.status', $inactiveUser).'"', false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('action="'.route('users.status', $activeUser).'"', false)
            ->assertSee('return confirm(', false);
    }

    public function test_disabled_user_loses_an_existing_authenticated_session(): void
    {
        $user = User::factory()->create([
            'email' => null,
            'nidn' => '001234',
            'role' => UserRole::Dosen->value,
            'password' => 'password',
        ]);

        $this->post(route('login'), [
            'role' => UserRole::Dosen->value,
            'identity' => '001234',
            'password' => 'password',
        ])->assertRedirectToRoute('dashboard');

        User::query()->whereKey($user->id)->update(['is_active' => false]);

        $response = $this->get(route('dashboard'));

        $response->assertRedirectToRoute('login')
            ->assertSessionHasErrors('identity');
        $this->assertGuest();
        $this->get(route('login'))->assertOk();
    }

    public function test_disabled_user_is_logged_out_before_accessing_other_authenticated_routes(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin->value,
            'password' => 'password',
        ]);
        $this->post(route('login'), [
            'role' => UserRole::Admin->value,
            'identity' => $admin->email,
            'password' => 'password',
        ])->assertRedirectToRoute('dashboard');

        User::query()->whereKey($admin->id)->update(['is_active' => false]);

        $response = $this->get(route('users.index'));

        $response->assertRedirectToRoute('login');
        $this->assertGuest();
    }
}

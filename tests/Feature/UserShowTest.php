<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{UserRole, string, string, string}>
     */
    public static function usersWithRoleIdentity(): array
    {
        return [
            'admin email' => [UserRole::Admin, 'email', 'detail-admin@example.com', 'Alamat email'],
            'dosen NIDN' => [UserRole::Dosen, 'nidn', '001234', 'NIDN'],
            'mahasiswa NIM' => [UserRole::Mahasiswa, 'nim', '0012345678', 'NIM'],
        ];
    }

    #[DataProvider('usersWithRoleIdentity')]
    public function test_admin_can_view_user_details_with_role_specific_identity(
        UserRole $role,
        string $identityField,
        string $identity,
        string $identityLabel,
    ): void {
        $user = User::factory()->create([
            'email' => $identityField === 'email' ? $identity : null,
            'nidn' => $identityField === 'nidn' ? $identity : null,
            'nim' => $identityField === 'nim' ? $identity : null,
            'name' => 'Nama Pengguna Detail',
            'role' => $role->value,
        ]);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->get(route('users.show', $user));

        $response->assertOk()
            ->assertSeeText('Detail Pengguna')
            ->assertSeeText('Nama Pengguna Detail')
            ->assertSeeText($role->name)
            ->assertSeeText($identityLabel)
            ->assertSeeText($identity)
            ->assertSee('href="'.route('users.index').'"', false)
            ->assertSeeText('Kembali ke Daftar')
            ->assertSee('href="'.route('users.edit', $user).'"', false)
            ->assertSeeText('Edit Pengguna')
            ->assertDontSee($user->password);
    }

    public function test_guest_is_redirected_to_login_from_user_details(): void
    {
        $user = User::factory()->create(['role' => UserRole::Mahasiswa->value]);

        $response = $this->get(route('users.show', $user));

        $response->assertRedirectToRoute('login');
    }

    public function test_user_name_is_escaped_in_user_details(): void
    {
        $user = User::factory()->create([
            'name' => "<script>alert('xss')</script>",
            'role' => UserRole::Dosen->value,
            'nidn' => '001234',
        ]);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->get(route('users.show', $user));

        $response->assertOk()
            ->assertSee('&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;', false)
            ->assertDontSee("<script>alert('xss')</script>", false);
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
    public function test_non_admin_cannot_view_user_details(UserRole $role): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin->value]);
        $actor = User::factory()->create(['role' => $role->value]);

        $response = $this->actingAs($actor)->get(route('users.show', $user));

        $response->assertForbidden();
    }

    public function test_missing_user_returns_not_found(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->get(route('users.show', 999999));

        $response->assertNotFound();
    }
}

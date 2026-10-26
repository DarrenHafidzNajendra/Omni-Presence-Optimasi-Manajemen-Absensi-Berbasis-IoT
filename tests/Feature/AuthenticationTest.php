<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{UserRole, string, string}>
     */
    public static function roleIdentities(): array
    {
        return [
            'admin email' => [UserRole::Admin, 'email', 'admin@example.com'],
            'dosen NIDN' => [UserRole::Dosen, 'nidn', 'NIDN-001'],
            'mahasiswa NIM' => [UserRole::Mahasiswa, 'nim', 'NIM-001'],
        ];
    }

    #[DataProvider('roleIdentities')]
    public function test_user_can_log_in_with_identity_for_their_role(
        UserRole $role,
        string $identityColumn,
        string $identity,
    ): void {
        $user = $this->createUserForRole($role, $identityColumn, $identity);

        $response = $this->post(route('login'), [
            'role' => $role->value,
            'identity' => $identity,
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_invalid_credentials_without_authenticating(): void
    {
        $user = $this->createUserForRole(UserRole::Dosen, 'nidn', 'NIDN-INVALID-PASSWORD');

        $response = $this->post(route('login'), [
            'role' => UserRole::Dosen->value,
            'identity' => $user->nidn,
            'password' => 'incorrect-password',
        ]);

        $response->assertSessionHasErrors('identity');
        $this->assertGuest();
    }

    #[DataProvider('roleIdentities')]
    public function test_inactive_users_cannot_log_in_for_their_role(
        UserRole $role,
        string $identityColumn,
        string $identity,
    ): void {
        $user = $this->createUserForRole($role, $identityColumn, $identity);
        $user->is_active = false;
        $user->save();

        $response = $this->post(route('login'), [
            'role' => $role->value,
            'identity' => $identity,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('identity');
        $this->assertGuest();
    }

    /**
     * @return array<string, array{UserRole, string, string, UserRole, string}>
     */
    public static function inactiveRoleMismatches(): array
    {
        return [
            'admin identity submitted as dosen' => [
                UserRole::Admin,
                'email',
                'inactive-admin@example.com',
                UserRole::Dosen,
                'nidn',
            ],
            'dosen identity submitted as admin' => [
                UserRole::Dosen,
                'nidn',
                'inactive-dosen@example.com',
                UserRole::Admin,
                'email',
            ],
            'mahasiswa identity submitted as admin' => [
                UserRole::Mahasiswa,
                'nim',
                'inactive-mahasiswa@example.com',
                UserRole::Admin,
                'email',
            ],
        ];
    }

    #[DataProvider('inactiveRoleMismatches')]
    public function test_inactive_users_cannot_log_in_with_another_selected_role(
        UserRole $actualRole,
        string $identityColumn,
        string $identity,
        UserRole $selectedRole,
        string $selectedIdentityColumn,
    ): void {
        $user = $this->createUserForRole($actualRole, $identityColumn, $identity);
        $user->forceFill([
            $selectedIdentityColumn => $identity,
            'is_active' => false,
        ])->save();

        $response = $this->post(route('login'), [
            'role' => $selectedRole->value,
            'identity' => $identity,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('identity');
        $this->assertGuest();
    }

    public function test_login_rejects_an_identity_submitted_under_a_different_role(): void
    {
        $user = $this->createUserForRole(UserRole::Dosen, 'nidn', 'SHARED-IDENTIFIER');
        $user->forceFill(['nim' => 'SHARED-IDENTIFIER'])->save();

        $response = $this->post(route('login'), [
            'role' => UserRole::Mahasiswa->value,
            'identity' => 'SHARED-IDENTIFIER',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('identity');
        $this->assertGuest();
    }

    public function test_user_with_an_unsupported_database_role_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'unsupported@example.com',
            'role' => 'operator',
        ]);

        $response = $this->post(route('login'), [
            'role' => UserRole::Admin->value,
            'identity' => $user->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('identity');
        $this->assertGuest();
    }

    public function test_successful_login_regenerates_the_session_id(): void
    {
        $user = $this->createUserForRole(UserRole::Admin, 'email', 'session@example.com');
        $this->get(route('login'));
        $sessionIdBeforeLogin = session()->getId();

        $response = $this->post(route('login'), [
            'role' => UserRole::Admin->value,
            'identity' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertNotSame($sessionIdBeforeLogin, session()->getId());
    }

    public function test_authenticated_user_can_log_out(): void
    {
        $user = $this->createUserForRole(UserRole::Mahasiswa, 'nim', 'NIM-LOGOUT');

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirectToRoute('login');
        $this->assertGuest();
    }

    private function createUserForRole(UserRole $role, string $identityColumn, string $identity): User
    {
        return User::factory()->create([
            'email' => $identityColumn === 'email' ? $identity : null,
            'nidn' => $identityColumn === 'nidn' ? $identity : null,
            'nim' => $identityColumn === 'nim' ? $identity : null,
            'password' => 'password',
            'role' => $role->value,
        ]);
    }
}

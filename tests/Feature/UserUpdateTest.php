<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_edit_form_with_current_values_and_without_password(): void
    {
        $user = $this->createUserForRole(UserRole::Dosen, 'nidn', '001234');
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->get(route('users.edit', $user));

        $response->assertOk()
            ->assertSeeText('Edit Pengguna')
            ->assertSee('name="name"', false)
            ->assertSee('value="'.$user->name.'"', false)
            ->assertSee('value="dosen" selected', false)
            ->assertSee('value="001234"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSeeText('Simpan Perubahan')
            ->assertSeeText('Batal')
            ->assertDontSee($user->password);
    }

    /**
     * @return array<string, array{UserRole, string, string, string}>
     */
    public static function roleIdentitiesToUpdate(): array
    {
        return [
            'admin email' => [UserRole::Admin, 'email', 'old-admin@example.com', 'new-admin@example.com'],
            'dosen NIDN' => [UserRole::Dosen, 'nidn', '001234', '009876'],
            'mahasiswa NIM' => [UserRole::Mahasiswa, 'nim', '0012345678', '0098765432'],
        ];
    }

    #[DataProvider('roleIdentitiesToUpdate')]
    public function test_admin_can_update_name_and_identity_for_each_role(
        UserRole $role,
        string $identityField,
        string $oldIdentity,
        string $newIdentity,
    ): void {
        $user = $this->createUserForRole($role, $identityField, $oldIdentity);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Nama Diperbarui',
            $identityField => $newIdentity,
            'role' => $role->value,
        ]);

        $response->assertRedirectToRoute('users.index')
            ->assertSessionHas('success', 'Pengguna berhasil diperbarui.');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Diperbarui',
            $identityField => $newIdentity,
            'role' => $role->value,
        ]);
    }

    /**
     * @return array<string, array{UserRole, string, string}>
     */
    public static function unchangedRoleIdentities(): array
    {
        return [
            'admin email' => [UserRole::Admin, 'email', 'same-admin@example.com'],
            'dosen NIDN' => [UserRole::Dosen, 'nidn', '001234'],
            'mahasiswa NIM' => [UserRole::Mahasiswa, 'nim', '0012345678'],
        ];
    }

    #[DataProvider('unchangedRoleIdentities')]
    public function test_existing_identity_is_allowed_when_it_belongs_to_the_user_being_updated(
        UserRole $role,
        string $identityField,
        string $identity,
    ): void {
        $user = $this->createUserForRole($role, $identityField, $identity);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Nama Tetap',
            $identityField => $identity,
            'role' => $role->value,
        ]);

        $response->assertRedirectToRoute('users.index');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            $identityField => $identity,
        ]);
    }

    /**
     * @return array<string, array{UserRole, string, string, UserRole, string, string}>
     */
    public static function duplicateRoleIdentities(): array
    {
        return [
            'admin email' => [UserRole::Admin, 'email', 'admin-one@example.com', UserRole::Admin, 'email', 'admin-two@example.com'],
            'dosen NIDN' => [UserRole::Dosen, 'nidn', '001234', UserRole::Dosen, 'nidn', '009876'],
            'mahasiswa NIM' => [UserRole::Mahasiswa, 'nim', '0012345678', UserRole::Mahasiswa, 'nim', '0098765432'],
        ];
    }

    #[DataProvider('duplicateRoleIdentities')]
    public function test_identity_owned_by_another_user_is_rejected(
        UserRole $role,
        string $identityField,
        string $identity,
        UserRole $otherRole,
        string $otherIdentityField,
        string $otherIdentity,
    ): void {
        $user = $this->createUserForRole($role, $identityField, $identity);
        $otherUser = $this->createUserForRole($otherRole, $otherIdentityField, $otherIdentity);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->from(route('users.edit', $user))->put(route('users.update', $user), [
            'name' => 'Tidak Boleh Tersimpan',
            $identityField => $otherIdentity,
            'role' => $role->value,
        ]);

        $response->assertRedirectToRoute('users.edit', $user)
            ->assertSessionHasErrors($identityField)
            ->assertSessionHas('_old_input.name', 'Tidak Boleh Tersimpan');
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => $user->name, $identityField => $identity]);
        $this->assertModelExists($otherUser);
    }

    public function test_changing_role_clears_identities_that_do_not_apply_to_the_new_role(): void
    {
        $user = $this->createUserForRole(UserRole::Dosen, 'nidn', '001234');
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'nim' => '0012345678',
            'role' => UserRole::Mahasiswa->value,
        ]);

        $response->assertRedirectToRoute('users.index');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => null,
            'nidn' => null,
            'nim' => '0012345678',
            'role' => UserRole::Mahasiswa->value,
        ]);
    }

    /**
     * @return array<string, array{UserRole, string, string}>
     */
    public static function invalidRoleIdentities(): array
    {
        return [
            'NIDN too short' => [UserRole::Dosen, 'nidn', '12345'],
            'NIDN too long' => [UserRole::Dosen, 'nidn', '1234567'],
            'NIDN letters' => [UserRole::Dosen, 'nidn', '12A456'],
            'NIDN symbols' => [UserRole::Dosen, 'nidn', '12-456'],
            'NIM too short' => [UserRole::Mahasiswa, 'nim', '123456789'],
            'NIM too long' => [UserRole::Mahasiswa, 'nim', '12345678901'],
            'NIM letters' => [UserRole::Mahasiswa, 'nim', '123456789A'],
            'NIM symbols' => [UserRole::Mahasiswa, 'nim', '12345678-0'],
        ];
    }

    #[DataProvider('invalidRoleIdentities')]
    public function test_invalid_nidn_or_nim_is_rejected(
        UserRole $role,
        string $identityField,
        string $invalidIdentity,
    ): void {
        $user = $this->createUserForRole($role, $identityField, $role === UserRole::Dosen ? '001234' : '0012345678');
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->from(route('users.edit', $user))->put(route('users.update', $user), [
            'name' => $user->name,
            $identityField => $invalidIdentity,
            'role' => $role->value,
        ]);

        $response->assertRedirectToRoute('users.edit', $user)
            ->assertSessionHasErrors($identityField);
    }

    public function test_empty_password_preserves_the_existing_password(): void
    {
        $user = $this->createUserForRole(UserRole::Admin, 'email', 'password-preserve@example.com');
        $originalPassword = $user->password;
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => UserRole::Admin->value,
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirectToRoute('users.index');
        $this->assertSame($originalPassword, $user->fresh()->password);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_new_password_is_hashed_and_can_be_verified(): void
    {
        $user = $this->createUserForRole(UserRole::Admin, 'email', 'password-change@example.com');
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => UserRole::Admin->value,
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
        ]);

        $response->assertRedirectToRoute('users.index');
        $this->assertNotSame('replacement-password', $user->fresh()->password);
        $this->assertTrue(Hash::check('replacement-password', $user->fresh()->password));
    }

    public function test_short_password_is_rejected_without_changing_the_existing_password(): void
    {
        $user = $this->createUserForRole(UserRole::Admin, 'email', 'password-short@example.com');
        $originalPassword = $user->password;
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->from(route('users.edit', $user))->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => UserRole::Admin->value,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertRedirectToRoute('users.edit', $user)
            ->assertSessionHasErrors('password')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');
        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_mismatched_password_confirmation_is_rejected(): void
    {
        $user = $this->createUserForRole(UserRole::Admin, 'email', 'password-mismatch@example.com');
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => UserRole::Admin->value,
            'password' => 'replacement-password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonAdminRoles(): array
    {
        return [
            'dosen' => [UserRole::Dosen->value],
            'mahasiswa' => [UserRole::Mahasiswa->value],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_cannot_open_or_submit_user_edits(string $role): void
    {
        $user = $this->createUserForRole(
            UserRole::Mahasiswa,
            'nim',
            '0012345678',
        );
        $actor = User::factory()->create(['role' => $role]);

        $this->actingAs($actor)->get(route('users.edit', $user))->assertForbidden();

        $this->put(route('users.update', $user), [
            'name' => 'Tidak Boleh Tersimpan',
            'nim' => '0098765432',
            'role' => UserRole::Mahasiswa->value,
        ])->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
            'nim' => '0012345678',
        ]);
    }

    public function test_guest_is_redirected_when_opening_or_submitting_user_edits(): void
    {
        $user = $this->createUserForRole(UserRole::Mahasiswa, 'nim', '0012345678');

        $this->get(route('users.edit', $user))->assertRedirectToRoute('login');
        $this->put(route('users.update', $user), [])->assertRedirectToRoute('login');
    }

    public function test_missing_user_returns_not_found_for_edit_and_update(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $this->actingAs($admin);

        $this->get(route('users.edit', 999999))->assertNotFound();
        $this->put(route('users.update', 999999), [])->assertNotFound();
    }

    private function createUserForRole(UserRole $role, string $identityField, string $identity): User
    {
        return User::factory()->create([
            'email' => $identityField === 'email' ? $identity : null,
            'nidn' => $identityField === 'nidn' ? $identity : null,
            'nim' => $identityField === 'nim' ? $identity : null,
            'password' => 'password',
            'role' => $role->value,
        ]);
    }
}

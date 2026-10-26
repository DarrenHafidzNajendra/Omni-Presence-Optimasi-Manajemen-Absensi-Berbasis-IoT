<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_create_user_form(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertOk()
            ->assertSeeText('Tambah Pengguna')
            ->assertSee('name="name"', false)
            ->assertSee('name="role"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="nim"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('name="nidn"', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('pattern="[0-9]{6}"', false)
            ->assertSee('pattern="[0-9]{10}"', false)
            ->assertSee('value="admin"', false)
            ->assertSee('value="dosen"', false)
            ->assertSee('value="mahasiswa"', false);
    }

    public function test_admin_can_create_a_user_with_a_hashed_password(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Dewi Lestari',
            'email' => 'dewi@example.com',
            'role' => UserRole::Admin->value,
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertRedirectToRoute('users.index')
            ->assertSessionHas('success', 'Pengguna berhasil ditambahkan.');

        $user = User::query()->where('email', 'dewi@example.com')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'name' => 'Dewi Lestari',
            'email' => 'dewi@example.com',
            'nidn' => null,
            'nim' => null,
            'role' => UserRole::Admin->value,
        ]);
        $this->assertNotSame('strong-password', $user->password);
        $this->assertTrue(Hash::check('strong-password', $user->password));
    }

    public function test_duplicate_email_is_rejected_and_safe_input_is_preserved(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->from(route('users.create'))->post(route('users.store'), [
            'name' => 'Nama Baru',
            'email' => 'existing@example.com',
            'role' => UserRole::Admin->value,
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertRedirectToRoute('users.create')
            ->assertSessionHasErrors('email')
            ->assertSessionHas('_old_input.name', 'Nama Baru')
            ->assertSessionHas('_old_input.email', 'existing@example.com')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionMissing('_old_input.password_confirmation');
    }

    public function test_role_outside_the_enum_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Pengguna Baru',
            'email' => 'pengguna@example.com',
            'role' => 'operator',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['name' => 'Pengguna Baru']);
    }

    public function test_admin_email_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Pengguna Baru',
            'role' => UserRole::Admin->value,
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_dosen_nidn_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Dosen Baru',
            'role' => UserRole::Dosen->value,
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertSessionHasErrors('nidn');
    }

    public function test_mahasiswa_nim_is_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Mahasiswa Baru',
            'role' => UserRole::Mahasiswa->value,
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertSessionHasErrors('nim');
    }

    public function test_name_role_and_password_are_required(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'role' => UserRole::Admin->value,
        ]);

        $response->assertSessionHasErrors(['name', 'password', 'email']);
    }

    public function test_password_confirmation_must_match(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Pengguna Baru',
            'nim' => '0123456789',
            'role' => UserRole::Mahasiswa->value,
            'password' => 'strong-password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['nim' => 'NIM-001']);
    }

    public function test_password_must_contain_at_least_eight_characters(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Pengguna Baru',
            'nim' => '0123456789',
            'role' => UserRole::Mahasiswa->value,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['nim' => 'NIM-002']);
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function uniqueRoleIdentities(): array
    {
        return [
            'admin email' => [UserRole::Admin->value, 'email', 'duplicate@example.com'],
            'dosen NIDN' => [UserRole::Dosen->value, 'nidn', '123456'],
            'mahasiswa NIM' => [UserRole::Mahasiswa->value, 'nim', '1234567890'],
        ];
    }

    #[DataProvider('uniqueRoleIdentities')]
    public function test_duplicate_role_identity_is_rejected(string $role, string $identityField, string $identityValue): void
    {
        User::factory()->create([$identityField => $identityValue]);
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Pengguna Duplikat',
            $identityField => $identityValue,
            'role' => $role,
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertSessionHasErrors($identityField);
        $this->assertDatabaseMissing('users', ['name' => 'Pengguna Duplikat']);
    }

    public function test_email_and_role_identity_columns_are_nullable_and_identity_columns_are_unique(): void
    {
        $columns = collect(Schema::getColumns('users'))->keyBy('name');
        $indexes = collect(Schema::getIndexes('users'))
            ->filter(fn (array $index): bool => $index['unique'])
            ->flatMap(fn (array $index): array => $index['columns'])
            ->all();

        $this->assertTrue($columns['email']['nullable']);
        $this->assertTrue($columns['nidn']['nullable']);
        $this->assertTrue($columns['nim']['nullable']);
        $this->assertContains('email', $indexes);
        $this->assertContains('nidn', $indexes);
        $this->assertContains('nim', $indexes);
    }

    /**
     * @return array<string, array{string, string, string, string|null, string|null, string|null}>
     */
    public static function validRoleIdentities(): array
    {
        return [
            'admin email' => [UserRole::Admin->value, 'email', 'admin-new@example.com', 'admin-new@example.com', null, null],
            'dosen NIDN' => [UserRole::Dosen->value, 'nidn', '123456', null, '123456', null],
            'mahasiswa NIM' => [UserRole::Mahasiswa->value, 'nim', '1234567890', null, null, '1234567890'],
        ];
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function invalidRoleIdentities(): array
    {
        return [
            'NIDN too short' => [UserRole::Dosen->value, 'nidn', '12345'],
            'NIDN too long' => [UserRole::Dosen->value, 'nidn', '1234567'],
            'NIDN contains letters' => [UserRole::Dosen->value, 'nidn', '12A456'],
            'NIDN contains symbols' => [UserRole::Dosen->value, 'nidn', '12-456'],
            'NIDN contains whitespace' => [UserRole::Dosen->value, 'nidn', '12 456'],
            'NIDN contains non-ASCII digits' => [UserRole::Dosen->value, 'nidn', '١٢٣٤٥٦'],
            'NIM too short' => [UserRole::Mahasiswa->value, 'nim', '123456789'],
            'NIM too long' => [UserRole::Mahasiswa->value, 'nim', '12345678901'],
            'NIM contains letters' => [UserRole::Mahasiswa->value, 'nim', '123456789A'],
            'NIM contains symbols' => [UserRole::Mahasiswa->value, 'nim', '12345678-0'],
            'NIM contains whitespace' => [UserRole::Mahasiswa->value, 'nim', '1234 67890'],
            'NIM contains non-ASCII digits' => [UserRole::Mahasiswa->value, 'nim', '٠١٢٣٤٥٦٧٨٩'],
        ];
    }

    #[DataProvider('invalidRoleIdentities')]
    public function test_role_identity_must_contain_only_the_exact_number_of_ascii_digits(
        string $role,
        string $identityField,
        string $identityValue,
    ): void {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Identitas Tidak Valid',
            $identityField => $identityValue,
            'role' => $role,
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertSessionHasErrors($identityField);
        $this->assertDatabaseMissing('users', [$identityField => $identityValue]);
    }

    public function test_nidn_and_nim_with_leading_zeroes_are_stored_as_strings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Dosen Nol Awal',
            'nidn' => '001234',
            'role' => UserRole::Dosen->value,
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ])->assertRedirectToRoute('users.index');

        $this->post(route('users.store'), [
            'name' => 'Mahasiswa Nol Awal',
            'nim' => '0012345678',
            'role' => UserRole::Mahasiswa->value,
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ])->assertRedirectToRoute('users.index');

        $this->assertSame('001234', User::query()->where('name', 'Dosen Nol Awal')->value('nidn'));
        $this->assertSame('0012345678', User::query()->where('name', 'Mahasiswa Nol Awal')->value('nim'));
    }

    #[DataProvider('validRoleIdentities')]
    public function test_each_role_can_be_created_with_only_its_identity(
        string $role,
        string $identityField,
        string $identityValue,
        ?string $expectedEmail,
        ?string $expectedNidn,
        ?string $expectedNim,
    ): void {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $identity = [$identityField => $identityValue];

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Akun '.ucfirst($role),
            ...$identity,
            'role' => $role,
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertRedirectToRoute('users.index');
        $user = User::query()->where($identityField, $identityValue)->firstOrFail();
        $this->assertSame($identityValue, $user->{$identityField});
        $this->assertSame($expectedEmail, $user->email);
        $this->assertSame($expectedNidn, $user->nidn);
        $this->assertSame($expectedNim, $user->nim);
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
    public function test_non_admin_cannot_open_the_create_user_form(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->get(route('users.create'));

        $response->assertForbidden();
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_cannot_submit_the_create_user_form(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $response = $this->actingAs($user)->post(route('users.store'), [
            'name' => 'Tidak Diizinkan',
            'nim' => 'NIM-NOT-ALLOWED',
            'role' => UserRole::Mahasiswa->value,
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['nim' => 'NIM-NOT-ALLOWED']);
    }

    public function test_guest_is_redirected_to_login_from_the_create_user_form(): void
    {
        $response = $this->get(route('users.create'));

        $response->assertRedirectToRoute('login');
    }

    public function test_guest_is_redirected_to_login_when_submitting_the_create_user_form(): void
    {
        $response = $this->post(route('users.store'), [
            'name' => 'Tidak Diizinkan',
            'nim' => 'NIM-NOT-ALLOWED',
            'role' => UserRole::Mahasiswa->value,
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response->assertRedirectToRoute('login');
        $this->assertDatabaseMissing('users', ['nim' => 'NIM-NOT-ALLOWED']);
    }
}

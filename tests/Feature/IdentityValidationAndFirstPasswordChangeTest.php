<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DevelopmentTestingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IdentityValidationAndFirstPasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_nidn_and_nim_must_be_exactly_ten_numeric_digits_when_created_by_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        // Dosen NIDN kurang dari 10 digit ditolak
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Dosen Pendek',
            'role' => UserRole::Dosen->value,
            'nidn' => '123456789', // 9 digit
        ])->assertSessionHasErrors('nidn');

        // Dosen NIDN lebih dari 10 digit ditolak
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Dosen Panjang',
            'role' => UserRole::Dosen->value,
            'nidn' => '12345678901', // 11 digit
        ])->assertSessionHasErrors('nidn');

        // Dosen NIDN mengandung huruf ditolak
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Dosen Huruf',
            'role' => UserRole::Dosen->value,
            'nidn' => '123456789A',
        ])->assertSessionHasErrors('nidn');

        // Mahasiswa NIM kurang dari 10 digit ditolak
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Mhs Pendek',
            'role' => UserRole::Mahasiswa->value,
            'nim' => '123456789',
        ])->assertSessionHasErrors('nim');

        // Mahasiswa NIM lebih dari 10 digit ditolak
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Mhs Panjang',
            'role' => UserRole::Mahasiswa->value,
            'nim' => '12345678901',
        ])->assertSessionHasErrors('nim');

        // Mahasiswa NIM mengandung simbol ditolak
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Mhs Simbol',
            'role' => UserRole::Mahasiswa->value,
            'nim' => '12345678-0',
        ])->assertSessionHasErrors('nim');
    }

    public function test_nidn_and_nim_with_ten_digits_are_accepted_and_leading_zeroes_preserved(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Dosen Nol Awal',
            'role' => UserRole::Dosen->value,
            'nidn' => '0012345678',
        ])->assertRedirectToRoute('users.index');

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Mhs Nol Awal',
            'role' => UserRole::Mahasiswa->value,
            'nim' => '0098765432',
        ])->assertRedirectToRoute('users.index');

        $dosen = User::where('name', 'Dosen Nol Awal')->firstOrFail();
        $this->assertSame('0012345678', $dosen->nidn);
        $this->assertTrue($dosen->must_change_password);
        $this->assertTrue(Hash::check('0012345678', $dosen->password));

        $mhs = User::where('name', 'Mhs Nol Awal')->firstOrFail();
        $this->assertSame('0098765432', $mhs->nim);
        $this->assertTrue($mhs->must_change_password);
        $this->assertTrue(Hash::check('0098765432', $mhs->password));
    }

    public function test_login_rejects_non_ten_digit_identity_for_dosen_and_mahasiswa(): void
    {
        // Login Dosen dengan NIDN kurang dari 10 digit
        $this->post(route('login'), [
            'role' => UserRole::Dosen->value,
            'identity' => '123456',
            'password' => 'password123',
        ])->assertSessionHasErrors('identity');

        // Login Mahasiswa dengan NIM lebih dari 10 digit
        $this->post(route('login'), [
            'role' => UserRole::Mahasiswa->value,
            'identity' => '1234567890123',
            'password' => 'password123',
        ])->assertSessionHasErrors('identity');
    }

    public function test_first_login_forces_password_change_and_blocks_dashboard(): void
    {
        $dosen = User::factory()->create([
            'nidn' => '0001018501',
            'role' => UserRole::Dosen->value,
            'password' => Hash::make('0001018501'),
            'must_change_password' => true,
            'is_active' => true,
        ]);

        // Login dengan password default
        $response = $this->post(route('login'), [
            'role' => UserRole::Dosen->value,
            'identity' => '0001018501',
            'password' => '0001018501',
        ]);

        $response->assertRedirectToRoute('password.first-change');
        $this->assertAuthenticatedAs($dosen);

        // Mencoba mengakses dashboard secara langsung diblokir dan diarahkan ke ganti password
        $this->actingAs($dosen)->get(route('dashboard'))
            ->assertRedirectToRoute('password.first-change');

        // Form ganti password dapat dibuka
        $this->actingAs($dosen)->get(route('password.first-change'))
            ->assertOk()
            ->assertSeeText('Ganti Kata Sandi Awal');
    }

    public function test_user_can_change_password_and_then_access_dashboard_normally(): void
    {
        $mhs = User::factory()->create([
            'nim' => '3312501001',
            'role' => UserRole::Mahasiswa->value,
            'password' => Hash::make('3312501001'),
            'must_change_password' => true,
            'is_active' => true,
        ]);

        $this->actingAs($mhs)->post(route('password.first-change.update'), [
            'password' => 'PasswordBaru123!',
            'password_confirmation' => 'PasswordBaru123!',
        ])->assertRedirectToRoute('dashboard');

        $mhs->refresh();
        $this->assertFalse($mhs->must_change_password);
        $this->assertTrue(Hash::check('PasswordBaru123!', $mhs->password));

        // Sekarang mahasiswa dapat membuka dashboard tanpa dicegat
        $this->actingAs($mhs)->get(route('dashboard'))
            ->assertOk();

        // Logout dan login kembali menggunakan password baru
        $this->post(route('logout'));

        $this->post(route('login'), [
            'role' => UserRole::Mahasiswa->value,
            'identity' => '3312501001',
            'password' => 'PasswordBaru123!',
        ])->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($mhs);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'nim' => '3312501002',
            'role' => UserRole::Mahasiswa->value,
            'password' => Hash::make('3312501002'),
            'is_active' => false,
            'must_change_password' => true,
        ]);

        $this->post(route('login'), [
            'role' => UserRole::Mahasiswa->value,
            'identity' => '3312501002',
            'password' => '3312501002',
        ])->assertSessionHasErrors('identity');

        $this->assertGuest();
    }

    public function test_admin_logins_normally_without_forced_password_change(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@omnipresence.local',
            'role' => UserRole::Admin->value,
            'password' => Hash::make('password123'),
            'must_change_password' => false,
            'is_active' => true,
        ]);

        $response = $this->post(route('login'), [
            'role' => UserRole::Admin->value,
            'identity' => 'admin@omnipresence.local',
            'password' => 'password123',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($admin);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_seeder_sets_nidn_nim_as_default_passwords_and_enables_must_change_password(): void
    {
        $this->seed(DevelopmentTestingSeeder::class);

        $dosen = User::where('nidn', '0001018501')->firstOrFail();
        $this->assertTrue(Hash::check('0001018501', $dosen->password));
        $this->assertTrue($dosen->must_change_password);

        $mhs = User::where('nim', '3312501001')->firstOrFail();
        $this->assertTrue(Hash::check('3312501001', $mhs->password));
        $this->assertTrue($mhs->must_change_password);

        $admin = User::where('email', 'admin@omnipresence.local')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $admin->password));
        $this->assertFalse($admin->must_change_password);
    }
}

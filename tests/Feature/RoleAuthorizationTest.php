<?php

namespace Tests\Feature;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function supportedRoles(): array
    {
        return [
            'admin' => ['admin'],
            'dosen' => ['dosen'],
            'mahasiswa' => ['mahasiswa'],
        ];
    }

    #[DataProvider('supportedRoles')]
    public function test_dashboard_accepts_each_supported_role(string $role): void
    {
        $user = User::factory()->make();
        $user->forceFill(['role' => $role]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    public function test_dashboard_forbids_users_with_an_unsupported_role(): void
    {
        $user = User::factory()->make();
        $user->forceFill(['role' => 'operator']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertForbidden();
    }
}

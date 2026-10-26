<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthenticationPagesTest extends TestCase
{
    public function test_guest_can_open_the_login_form(): void
    {
        $response = $this->get('/login');

        $response->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertDontSee('register');
    }

    public function test_login_rejects_missing_credentials(): void
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_guest_is_redirected_to_login_from_the_dashboard(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirectToRoute('login');
    }
}

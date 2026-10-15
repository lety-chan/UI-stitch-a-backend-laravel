<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginViewTest extends TestCase
{
    public function test_login_page_renders_a_csrf_protected_form(): void
    {
        $this->withoutVite();

        $response = $this->get('/login');

        $response->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('action="'.route('login.store').'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="email"', false);
    }
}

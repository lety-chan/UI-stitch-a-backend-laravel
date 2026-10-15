<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_a_user_and_starts_an_authenticated_session(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Carlos Mendoza',
            'email' => 'carlos@example.com',
            'company_name' => 'Logística Global Tech',
            'password' => 'Depeeper!2026',
            'password_confirmation' => 'Depeeper!2026',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('profile.show'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Carlos Mendoza',
            'email' => 'carlos@example.com',
            'company_name' => 'Logística Global Tech',
        ]);
    }

    public function test_registration_validates_required_fields_password_and_terms(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'company_name' => '',
            'password' => 'weak',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'company_name', 'password', 'password_confirmation', 'terms']);
        $this->assertGuest();
    }

    public function test_registration_page_renders_a_csrf_protected_form(): void
    {
        $this->withoutVite();

        $this->get(route('register'))
            ->assertOk()
            ->assertViewIs('auth.register')
            ->assertSee('name="_token"', false)
            ->assertSee('name="company_name"', false)
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_a_user_can_log_in_and_log_out(): void
    {
        User::factory()->create([
            'email' => 'carlos@example.com',
            'password' => Hash::make('Depeeper!2026'),
        ]);

        $this->post(route('login.store'), [
            'email' => 'carlos@example.com',
            'password' => 'Depeeper!2026',
        ])->assertRedirect(route('profile.show'));

        $this->assertAuthenticated();

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'carlos@example.com',
            'password' => Hash::make('Depeeper!2026'),
        ]);

        $this->post(route('login.store'), [
            'email' => 'carlos@example.com',
            'password' => 'incorrecta',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_view_and_update_their_profile(): void
    {
        $this->withoutVite();

        $user = User::factory()->create([
            'name' => 'Carlos Mendoza',
            'email' => 'carlos@example.com',
            'company_name' => 'Empresa anterior',
        ]);

        $this->actingAs($user)
            ->get(route('profile.show'))
            ->assertOk()
            ->assertViewIs('user.profile')
            ->assertSee('Carlos Mendoza')
            ->assertSee('Empresa anterior');

        $this->patch(route('profile.update'), [
            'name' => 'Carlos M.',
            'email' => 'carlos@example.com',
            'company_name' => 'Logística Global Tech',
        ])->assertRedirect(route('profile.show'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Carlos M.',
            'company_name' => 'Logística Global Tech',
        ]);
    }

    public function test_guests_are_redirected_from_the_profile(): void
    {
        $this->get(route('profile.show'))
            ->assertRedirect(route('login'));
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_displayed(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Application de gestion');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@sfmid.local',
            'password' => Hash::make('Password@123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'user@sfmid.local',
            'password' => 'Password@123',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->refresh()->last_login_at);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@sfmid.local',
            'password' => Hash::make('Password@123'),
            'is_active' => true,
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'user@sfmid.local',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'Les identifiants fournis sont incorrects.',
        ]);

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@sfmid.local',
            'password' => Hash::make('Password@123'),
            'is_active' => false,
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'inactive@sfmid.local',
            'password' => 'Password@123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'Les identifiants fournis sont incorrects.',
        ]);

        $this->assertGuest();
    }
}
